<?php
/**
 * Description of ExpressionManager
 * (1) Does safe evaluation of PHP expressions.  Only registered Functions, and known Variables are allowed.
 *   (a) Functions include any math, string processing, conditional, formatting, etc. functions
 * (2) This class replaces LimeSurvey's <= 1.91+  process of resolving strings that contain LimeReplacementFields
 *   (a) String is split by expressions (by curly braces, but safely supporting strings and escaped curly braces)
 *   (b) Expressions (things surrounded by curly braces) are evaluated - thereby doing LimeReplacementField substitution and/or more complex calculations
 *   (c) Non-expressions are left intact
 *   (d) The array of stringParts are re-joined to create the desired final string.
 * (3) The core of Expression Manager is a Recursive Descent Parser (RDP), based off of one build via JavaCC by TMSWhite in 1999.
 *   (a) Functions that start with RDP_ should not be touched unless you really understand compiler design.
 *
 * @author Thomas M. White (TMSWhite)
 */

class ExpressionManager {
    // These are the allowable suffixes for variables - each represents an attribute of a variable.
    static $RDP_regex_var_attr = 'code|gid|grelevance|gseq|jsName|mandatory|NAOK|qid|qseq|question|readWrite|relevanceStatus|relevance|rowdivid|sgqa|shown|type|valueNAOK|value';

    // These three variables are effectively static once constructed
    private $RDP_ExpressionRegex;
    private $RDP_TokenType;
    private $RDP_TokenizerRegex;
    private $RDP_CategorizeTokensRegex;
    private $RDP_ValidFunctions; // names and # params of valid functions

    // Thes variables are used while  processing the equation
    private $RDP_expr;  // the source expression
    private $RDP_tokens;    // the list of generated tokens
    private $RDP_count; // total number of $RDP_tokens
    private $RDP_pos;   // position within the $token array while processing equation
    private $RDP_errs;    // array of syntax errors
    private $RDP_onlyparse;
    private $RDP_stack; // stack of intermediate results
    private $RDP_result;    // final result of evaluating the expression;
    private $RDP_evalStatus;    // true if $RDP_result is a valid result, and  there are no serious errors
    private $varsUsed;  // list of variables referenced in the equation

    // These  variables are only used by sProcessStringContainingExpressions
    private $allVarsUsed;   // full list of variables used within the string, even if contains multiple expressions
    private $prettyPrintSource; // HTML formatted output of running sProcessStringContainingExpressions
    private $substitutionNum; // Keeps track of number of substitions performed XXX
    private $substitutionInfo; // array of JavaScripts to managing dynamic substitution
    private $jsExpression;  // caches computation of JavaScript equivalent for an Expression

    private $questionSeq;   // sequence order of question - so can detect if try to use variable before it is set
    private $groupSeq;  // sequence order of groups - so can detect if try to use variable before it is set
    private $surveyMode='group';

    // The following are only needed to enable click on variable names within pretty print and open new window to edit them
    private $sid=NULL; // the survey ID
    private $rooturl='';    // the root URL for LimeSurvey
    private $hyperlinkSyntaxHighlighting=false;
    private $sgqaNaming=false;

    function __construct()
    {
        // List of token-matching regular expressions
        // Note, this is effectively a Lexer using Regular Expressions.  Don't change this unless you understand compiler design.
        $RDP_regex_dq_string = '(?<!\\\\)".*?(?<!\\\\)"';
        $RDP_regex_sq_string = '(?<!\\\\)\'.*?(?<!\\\\)\'';
        $RDP_regex_whitespace = '\s+';
        $RDP_regex_lparen = '\(';
        $RDP_regex_rparen = '\)';
        $RDP_regex_comma = ',';
        $RDP_regex_not = '!';
        $RDP_regex_inc_dec = '\+\+|--';
        $RDP_regex_binary = '[+*/-]';
        $RDP_regex_compare = '<=|<|>=|>|==|!=|\ble\b|\blt\b|\bge\b|\bgt\b|\beq\b|\bne\b';
        $RDP_regex_assign = '=';    // '=|\+=|-=|\*=|/=';
        $RDP_regex_sgqa = '(?:INSERTANS:)?[0-9]+X[0-9]+X[0-9]+[A-Z0-9_]*\#?[01]?(?:\.(?:' . ExpressionManager::$RDP_regex_var_attr . '))?';
        $RDP_regex_word = '(?:TOKEN:|SAMPLE:|OPERATOR:|RESPONDENT:)?(?:[A-Z][A-Z0-9_]*)?(?:\.(?:[A-Z][A-Z0-9_]*))*(?:\.(?:' . ExpressionManager::$RDP_regex_var_attr . '))?';
        $RDP_regex_number = '[0-9]+\.?[0-9]*|\.[0-9]+';
        $RDP_regex_andor = '\band\b|\bor\b|&&|\|\|';
        $RDP_regex_lcb = '{';
        $RDP_regex_rcb = '}';
        $RDP_regex_sq = '\'';
        $RDP_regex_dq= '"';
        $RDP_regex_bs = '\\\\';

        $RDP_StringSplitRegex = array(
            $RDP_regex_lcb,
            $RDP_regex_rcb,
            $RDP_regex_sq,
            $RDP_regex_dq,
            $RDP_regex_bs,
        );

        // RDP_ExpressionRegex is the regular expression that splits apart strings that contain curly braces in order to find expressions
        $this->RDP_ExpressionRegex =  '#(' . implode('|',$RDP_StringSplitRegex) . ')#i';

        // asTokenRegex and RDP_TokenType must be kept in sync  (same number and order)
        $RDP_TokenRegex = array(
            $RDP_regex_dq_string,
            $RDP_regex_sq_string,
            $RDP_regex_whitespace,
            $RDP_regex_lparen,
            $RDP_regex_rparen,
            $RDP_regex_comma,
            $RDP_regex_andor,
            $RDP_regex_compare,
            $RDP_regex_sgqa,
            $RDP_regex_word,
            $RDP_regex_number,
            $RDP_regex_not,
            $RDP_regex_inc_dec,
            $RDP_regex_assign,
            $RDP_regex_binary,
            );

        $this->RDP_TokenType = array(
            'DQ_STRING',
            'SQ_STRING',
            'SPACE',
            'LP',
            'RP',
            'COMMA',
            'AND_OR',
            'COMPARE',
            'SGQA',
            'WORD',
            'NUMBER',
            'NOT',
            'OTHER',
            'ASSIGN',
            'BINARYOP',
           );

        // $RDP_TokenizerRegex - a single regex used to split and equation into tokens
        $this->RDP_TokenizerRegex = '#(' . implode('|',$RDP_TokenRegex) . ')#i';

        // $RDP_CategorizeTokensRegex - an array of patterns so can categorize the type of token found - would be nice if could get this from preg_split
        // Adding ability to capture 'OTHER' type, which indicates an error - unsupported syntax element
        $this->RDP_CategorizeTokensRegex = preg_replace("#^(.*)$#","#^$1$#i",$RDP_TokenRegex);
        $this->RDP_CategorizeTokensRegex[] = '/.+/';
        $this->RDP_TokenType[] = 'OTHER';

        // Each allowed function is a mapping from local name to external name + number of arguments
        // Functions can have a list of serveral allowable #s of arguments.
        // If the value is -1, the function must have a least one argument but can have an unlimited number of them
        // -2 means that at least one argument is required.  -3 means at least two arguments are required, etc.
        $this->RDP_ValidFunctions = array(
'abs' => array('abs', 'Math.abs', $this->gT('Absolute value'), 'number abs(number)', 'http://www.php.net/manual/en/function.checkdate.php', 1),
'acos' => array('acos', 'Math.acos', $this->gT('Arc cosine'), 'number acos(number)', 'http://www.php.net/manual/en/function.acos.php', 1),
'addslashes' => array('addslashes', $this->gT('addslashes'), 'Quote string with slashes', 'string addslashes(string)', 'http://www.php.net/manual/en/function.addslashes.php', 1),
'asin' => array('asin', 'Math.asin', $this->gT('Arc sine'), 'number asin(number)', 'http://www.php.net/manual/en/function.asin.php', 1),
'atan' => array('atan', 'Math.atan', $this->gT('Arc tangent'), 'number atan(number)', 'http://www.php.net/manual/en/function.atan.php', 1),
'atan2' => array('atan2', 'Math.atan2', $this->gT('Arc tangent of two variables'), 'number atan2(number, number)', 'http://www.php.net/manual/en/function.atan2.php', 2),
'ceil' => array('ceil', 'Math.ceil', $this->gT('Round fractions up'), 'number ceil(number)', 'http://www.php.net/manual/en/function.ceil.php', 1),
'checkdate' => array('checkdate', 'checkdate', $this->gT('Returns true(1) if it is a valid date in gregorian calendar'), 'bool checkdate(month,day,year)', 'http://www.php.net/manual/en/function.checkdate.php', 3),
'cos' => array('cos', 'Math.cos', $this->gT('Cosine'), 'number cos(number)', 'http://www.php.net/manual/en/function.cos.php', 1),
'count' => array('exprmgr_count', 'LEMcount', $this->gT('Count the number of answered questions in the list'), 'number count(arg1, arg2, ... argN)', '', -1),
'countif' => array('exprmgr_countif', 'LEMcountif', $this->gT('Count the number of answered questions in the list equal the first argument'), 'number countif(matches, arg1, arg2, ... argN)', '', -2),
'countifop' => array('exprmgr_countifop', 'LEMcountifop', $this->gT('Count the number of answered questions in the list which pass the criteria (arg op value)'), 'number countifop(op, value, arg1, arg2, ... argN)', '', -3),
'date' => array('date', 'date', $this->gT('Format a local date/time'), 'string date(format [, timestamp=time()])', 'http://www.php.net/manual/en/function.date.php', 1,2),
'exp' => array('exp', 'Math.exp', $this->gT('Calculates the exponent of e'), 'number exp(number)', 'http://www.php.net/manual/en/function.exp.php', 1),
'fixnum' => array('exprmgr_fixnum', 'LEMfixnum', $this->gT('Display numbers with comma as radix separator, if needed'), 'string fixnum(number)', '', 1),
'floor' => array('floor', 'Math.floor', $this->gT('Round fractions down'), 'number floor(number)', 'http://www.php.net/manual/en/function.floor.php', 1),
'gmdate' => array('gmdate', 'gmdate', $this->gT('Format a GMT date/time'), 'string gmdate(format [, timestamp=time()])', 'http://www.php.net/manual/en/function.gmdate.php', 1,2),
'html_entity_decode' => array('html_entity_decode', 'html_entity_decode', $this->gT('Convert all HTML entities to their applicable characters (always uses ENT_QUOTES and UTF-8)'), 'string html_entity_decode(string)', 'http://www.php.net/manual/en/function.html-entity-decode.php', 1),
'htmlentities' => array('htmlentities', 'htmlentities', $this->gT('Convert all applicable characters to HTML entities (always uses ENT_QUOTES and UTF-8)'), 'string htmlentities(string)', 'http://www.php.net/manual/en/function.htmlentities.php', 1),
'htmlspecialchars' => array('expr_mgr_htmlspecialchars', 'htmlspecialchars', $this->gT('Convert special characters to HTML entities (always uses ENT_QUOTES and UTF-8)'), 'string htmlspecialchars(string)', 'http://www.php.net/manual/en/function.htmlspecialchars.php', 1),
'htmlspecialchars_decode' => array('expr_mgr_htmlspecialchars_decode', 'htmlspecialchars_decode', $this->gT('Convert special HTML entities back to characters (always uses ENT_QUOTES and UTF-8)'), 'string htmlspecialchars_decode(string)', 'http://www.php.net/manual/en/function.htmlspecialchars-decode.php', 1),
'idate' => array('idate', 'idate', $this->gT('Format a local time/date as integer'), 'string idate(string [, timestamp=time()])', 'http://www.php.net/manual/en/function.idate.php', 1,2),
'if' => array('exprmgr_if', 'LEMif', $this->gT('Conditional processing'), 'if(test,result_if_true,result_if_false)', '', 3),
'implode' => array('exprmgr_implode', 'LEMimplode', $this->gT('Join array elements with a string'), 'string implode(glue,arg1,arg2,...,argN)', 'http://www.php.net/manual/en/function.implode.php', -2),
'intval' => array('intval', 'LEMintval', $this->gT('Get the integer value of a variable'), 'int intval(number [, base=10])', 'http://www.php.net/manual/en/function.intval.php', 1,2),
'is_empty' => array('exprmgr_empty', 'LEMempty', $this->gT('Determine whether a variable is considered to be empty'), 'bool is_empty(var)', 'http://www.php.net/manual/en/function.empty.php', 1),
'is_float' => array('is_float', 'LEMis_float', $this->gT('Finds whether the type of a variable is float'), 'bool is_float(var)', 'http://www.php.net/manual/en/function.is-float.php', 1),
'is_int' => array('is_int', 'LEMis_int', $this->gT('Find whether the type of a variable is integer'), 'bool is_int(var)', 'http://www.php.net/manual/en/function.is-int.php', 1),
'is_nan' => array('is_nan', 'isNaN', $this->gT('Finds whether a value is not a number'), 'bool is_nan(var)', 'http://www.php.net/manual/en/function.is-nan.php', 1),
'is_null' => array('is_null', 'LEMis_null', $this->gT('Finds whether a variable is NULL'), 'bool is_null(var)', 'http://www.php.net/manual/en/function.is-null.php', 1),
'is_numeric' => array('is_numeric', 'LEMis_numeric', $this->gT('Finds whether a variable is a number or a numeric string'), 'bool is_numeric(var)', 'http://www.php.net/manual/en/function.is-numeric.php', 1),
'is_string' => array('is_string', 'LEMis_string', $this->gT('Find whether the type of a variable is string'), 'bool is_string(var)', 'http://www.php.net/manual/en/function.is-string.php', 1),
'list' => array('exprmgr_list', 'LEMlist', $this->gT('Return comma-separated list of values'), 'string list(arg1, arg2, ... argN)', '', -2),
'log' => array('log', 'Math.log', $this->gT('Natural logarithm'), 'number log(number)', 'http://www.php.net/manual/en/function.log.php', 1),
'ltrim' => array('ltrim', 'ltrim', $this->gT('Strip whitespace (or other characters) from the beginning of a string'), 'string ltrim(string [, charlist])', 'http://www.php.net/manual/en/function.ltrim.php', 1,2),
'max' => array('max', 'Math.max', $this->gT('Find highest value'), 'number max(arg1, arg2, ... argN)', 'http://www.php.net/manual/en/function.max.php', -2),
'min' => array('min', 'Math.min', $this->gT('Find lowest value'), 'number min(arg1, arg2, ... argN)', 'http://www.php.net/manual/en/function.min.php', -2),
'mktime' => array('mktime', 'mktime', $this->gT('Get UNIX timestamp for a date (each of the 6 arguments are optional)'), 'number mktime([hour [, minute [, second [, month [, day [, year ]]]]]])', 'http://www.php.net/manual/en/function.mktime.php', 0,1,2,3,4,5,6),
'nl2br' => array('nl2br', 'nl2br', $this->gT('Inserts HTML line breaks before all newlines in a string'), 'string nl2br(string)', 'http://www.php.net/manual/en/function.nl2br.php', 1,1),
'number_format' => array('number_format', 'number_format', $this->gT('Format a number with grouped thousands'), 'string number_format(number)', 'http://www.php.net/manual/en/function.number-format.php', 1),
'pi' => array('pi', 'LEMpi', $this->gT('Get value of pi'), 'number pi()', '', 0),
'pow' => array('pow', 'Math.pow', $this->gT('Exponential expression'), 'number pow(base, exp)', 'http://www.php.net/manual/en/function.pow.php', 2),
'quoted_printable_decode' => array('quoted_printable_decode', 'quoted_printable_decode', $this->gT('Convert a quoted-printable string to an 8 bit string'), 'string quoted_printable_decode(string)', 'http://www.php.net/manual/en/function.quoted-printable-decode.php', 1),
'quoted_printable_encode' => array('quoted_printable_encode', 'quoted_printable_encode', $this->gT('Convert a 8 bit string to a quoted-printable string'), 'string quoted_printable_encode(string)', 'http://www.php.net/manual/en/function.quoted-printable-encode.php', 1),
'quotemeta' => array('quotemeta', 'quotemeta', $this->gT('Quote meta characters'), 'string quotemeta(string)', 'http://www.php.net/manual/en/function.quotemeta.php', 1),
'rand' => array('rand', 'rand', $this->gT('Generate a random integer'), 'int rand() OR int rand(min, max)', 'http://www.php.net/manual/en/function.rand.php', 0,2),
'regexMatch' => array('exprmgr_regexMatch', 'LEMregexMatch', $this->gT('Compare a string to a regular expression pattern'), 'bool regexMatch(pattern,input)', '', 2),
'round' => array('round', 'round', $this->gT('Rounds a number to an optional precision'), 'number round(val [, precision])', 'http://www.php.net/manual/en/function.round.php', 1,2),
'rtrim' => array('rtrim', 'rtrim', $this->gT('Strip whitespace (or other characters) from the end of a string'), 'string rtrim(string [, charlist])', 'http://www.php.net/manual/en/function.rtrim.php', 1,2),
'sin' => array('sin', 'Math.sin', $this->gT('Sine'), 'number sin(arg)', 'http://www.php.net/manual/en/function.sin.php', 1),
'sprintf' => array('sprintf', 'sprintf', $this->gT('Return a formatted string'), 'string sprintf(format, arg1, arg2, ... argN)', 'http://www.php.net/manual/en/function.sprintf.php', -2),
'sqrt' => array('sqrt', 'Math.sqrt', $this->gT('Square root'), 'number sqrt(arg)', 'http://www.php.net/manual/en/function.sqrt.php', 1),
'stddev' => array('exprmgr_stddev', 'LEMstddev', $this->gT('Calculate the Sample Standard Deviation for the list of numbers'), 'number stddev(arg1, arg2, ... argN)', '', -2),
'str_pad' => array('str_pad', 'str_pad', $this->gT('Pad a string to a certain length with another string'), 'string str_pad(input, pad_length [, pad_string])', 'http://www.php.net/manual/en/function.str-pad.php', 2,3),
'str_repeat' => array('str_repeat', 'str_repeat', $this->gT('Repeat a string'), 'string str_repeat(input, multiplier)', 'http://www.php.net/manual/en/function.str-repeat.php', 2),
'str_replace' => array('str_replace', 'LEMstr_replace', $this->gT('Replace all occurrences of the search string with the replacement string'), 'string str_replace(search,  replace, subject)', 'http://www.php.net/manual/en/function.str-replace.php', 3),
'strcasecmp' => array('strcasecmp', 'strcasecmp', $this->gT('Binary safe case-insensitive string comparison'), 'int strcasecmp(str1, str2)', 'http://www.php.net/manual/en/function.strcasecmp.php', 2),
'strcmp' => array('strcmp', 'strcmp', $this->gT('Binary safe string comparison'), 'int strcmp(str1, str2)', 'http://www.php.net/manual/en/function.strcmp.php', 2),
'strip_tags' => array('strip_tags', 'strip_tags', $this->gT('Strip HTML and PHP tags from a string'), 'string strip_tags(str, allowable_tags)', 'http://www.php.net/manual/en/function.strip-tags.php', 1,2),
'stripos' => array('stripos', 'stripos', $this->gT('Find position of first occurrence of a case-insensitive string'), 'int stripos(haystack, needle [, offset=0])', 'http://www.php.net/manual/en/function.stripos.php', 2,3),
'stripslashes' => array('stripslashes', 'stripslashes', $this->gT('Un-quotes a quoted string'), 'string stripslashes(string)', 'http://www.php.net/manual/en/function.stripslashes.php', 1),
'stristr' => array('stristr', 'stristr', $this->gT('Case-insensitive strstr'), 'string stristr(haystack, needle [, before_needle=false])', 'http://www.php.net/manual/en/function.stristr.php', 2,3),
'strlen' => array('strlen', 'LEMstrlen', $this->gT('Get string length'), 'int strlen(string)', 'http://www.php.net/manual/en/function.strlen.php', 1),
'strpos' => array('strpos', 'LEMstrpos', $this->gT('Find position of first occurrence of a string'), 'int strpos(haystack, needle [ offset=0])', 'http://www.php.net/manual/en/function.strpos.php', 2,3),
'strrev' => array('strrev', 'strrev', $this->gT('Reverse a string'), 'string strrev(string)', 'http://www.php.net/manual/en/function.strrev.php', 1),
'strstr' => array('strstr', 'strstr', $this->gT('Find first occurrence of a string'), 'string strstr(haystack, needle)', 'http://www.php.net/manual/en/function.strstr.php', 2),
'strtolower' => array('strtolower', 'LEMstrtolower', $this->gT('Make a string lowercase'), 'string strtolower(string)', 'http://www.php.net/manual/en/function.strtolower.php', 1),
'strtoupper' => array('strtoupper', 'LEMstrtoupper', $this->gT('Make a string uppercase'), 'string strtoupper(string)', 'http://www.php.net/manual/en/function.strtoupper.php', 1),
'substr' => array('substr', 'substr', $this->gT('Return part of a string'), 'string substr(string, start [, length])', 'http://www.php.net/manual/en/function.substr.php', 2,3),
'sum' => array('array_sum', 'LEMsum', $this->gT('Calculate the sum of values in an array'), 'number sum(arg1, arg2, ... argN)', '', -2),
'sumifop' => array('exprmgr_sumifop', 'LEMsumifop', $this->gT('Sum the values of answered questions in the list which pass the criteria (arg op value)'), 'number sumifop(op, value, arg1, arg2, ... argN)', '', -3),
'tan' => array('tan', 'Math.tan', $this->gT('Tangent'), 'number tan(arg)', 'http://www.php.net/manual/en/function.tan.php', 1),
'time' => array('time', 'time', $this->gT('Return current UNIX timestamp'), 'number time()', 'http://www.php.net/manual/en/function.time.php', 0),
'trim' => array('trim', 'trim', $this->gT('Strip whitespace (or other characters) from the beginning and end of a string'), 'string trim(string [, charlist])', 'http://www.php.net/manual/en/function.trim.php', 1,2),
'ucwords' => array('ucwords', 'ucwords', $this->gT('Uppercase the first character of each word in a string'), 'string ucwords(string)', 'http://www.php.net/manual/en/function.ucwords.php', 1),
'unique' => array('exprmgr_unique', 'LEMunique', $this->gT('Returns true if all non-empty responses are unique'), 'boolean unique(arg1, ..., argN)', '', -1),
        );

    }

    /**
     * Add an error to the error log
     *
     * @param <type> $errMsg
     * @param <type> $token
     */
    private function RDP_AddError($errMsg, $token)
    {
        $this->RDP_errs[] = array($errMsg, $token);
    }

    /**
     * RDP_EvaluateBinary() computes binary expressions, such as (a or b), (c * d), popping  the top two entries off the
     * stack and pushing the result back onto the stack.
     *
     * @param array $token
     * @return boolean - false if there is any error, else true
     */

       private function RDP_EvaluateBinary(array $token)
    {
        if (count($this->RDP_stack) < 2)
        {
            $this->RDP_AddError($this->gT("Unable to evaluate binary operator - fewer than 2 entries on stack"), $token);
            return false;
        }
        $arg2 = $this->RDP_StackPop();
        $arg1 = $this->RDP_StackPop();
        if (is_null($arg1) or is_null($arg2))
        {
            $this->RDP_AddError($this->gT("Invalid value(s) on the stack"), $token);
            return false;
        }
        // TODO:  try to determine datatype?
        $bNumericArg1 = is_numeric($arg1[0]) || $arg1[0] == '';
        $bNumericArg2 = is_numeric($arg2[0]) || $arg2[0] == '';
        $bStringArg1 = !$bNumericArg1 || $arg1[0] == '';
        $bStringArg2 = !$bNumericArg2 || $arg2[0] == '';
        $bBothNumeric = ($bNumericArg1 && $bNumericArg2);
        $bBothString = ($bStringArg1 && $bStringArg2);
        $bMismatchType = (!$bBothNumeric && !$bBothString);
        switch(strtolower($token[0]))
        {
            case 'or':
            case '||':
                $result = array(($arg1[0] or $arg2[0]),$token[1],'NUMBER');
                break;
            case 'and':
            case '&&':
                $result = array(($arg1[0] and $arg2[0]),$token[1],'NUMBER');
                break;
            case '==':
            case 'eq':
                $result = array(($arg1[0] == $arg2[0]),$token[1],'NUMBER');
                break;
            case '!=':
            case 'ne':
                $result = array(($arg1[0] != $arg2[0]),$token[1],'NUMBER');
                break;
            case '<':
            case 'lt':
                if ($bMismatchType) {
                    $result = array(false,$token[1],'NUMBER');
                }
                else {
                    $result = array(($arg1[0] < $arg2[0]),$token[1],'NUMBER');
                }
                break;
            case '<=';
            case 'le':
                if ($bMismatchType) {
                    $result = array(false,$token[1],'NUMBER');
                }
                else {
                    // Need this explicit comparison in order to be in agreement with JavaScript
                    if (($arg1[0] == '0' && $arg2[0] == '') || ($arg1[0] == '' && $arg2[0] == '0')) {
                        $result = array(true,$token[1],'NUMBER');
                    }
                    else {
                        $result = array(($arg1[0] <= $arg2[0]),$token[1],'NUMBER');
                    }
                }
                break;
            case '>':
            case 'gt':
                if ($bMismatchType) {
                    $result = array(false,$token[1],'NUMBER');
                }
                else {
                    // Need this explicit comparison in order to be in agreement with JavaScript
                    if (($arg1[0] == '0' && $arg2[0] == '') || ($arg1[0] == '' && $arg2[0] == '0')) {
                        $result = array(false,$token[1],'NUMBER');
                    }
                    else {
                        $result = array(($arg1[0] > $arg2[0]),$token[1],'NUMBER');
                    }
                }
                break;
            case '>=';
            case 'ge':
                if ($bMismatchType) {
                    $result = array(false,$token[1],'NUMBER');
                }
                else {
                    $result = array(($arg1[0] >= $arg2[0]),$token[1],'NUMBER');

                }
                break;
            case '+':
                if ($bBothNumeric) {
                    $result = array(($arg1[0] + $arg2[0]),$token[1],'NUMBER');
                }
                else if ($bBothString) {
                    $result = array($arg1[0] . $arg2[0],$token[1],'STRING');
                }
                else {
                    $result = array(NAN,$token[1],'NUMBER');
                }
                break;
            case '-':
                if ($bBothNumeric) {
                    $result = array(($arg1[0] - $arg2[0]),$token[1],'NUMBER');
                }
                else {
                    $result = array(NAN,$token[1],'NUMBER');
                }
                break;
            case '*':
                if ($bBothNumeric) {
                    $result = array(($arg1[0] * $arg2[0]),$token[1],'NUMBER');
                }
                else {
                    $result = array(NAN,$token[1],'NUMBER');
                }
                break;
            case '/';
                if ($bBothNumeric) {
                    if ($arg2[0] == 0) {
                        $result = array(NAN,$token[1],'NUMBER');
                    }
                    else {
                        $result = array(($arg1[0] / $arg2[0]),$token[1],'NUMBER');
                    }
                }
                else {
                    $result = array(NAN,$token[1],'NUMBER');
                }
                break;
        }
        $this->RDP_StackPush($result);
        return true;
    }

    /**
     * Processes operations like +a, -b, !c
     * @param array $token
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateUnary(array $token)
    {
        if (count($this->RDP_stack) < 1)
        {
            $this->RDP_AddError($this->gT("Unable to evaluate unary operator - no entries on stack"), $token);
            return false;
        }
        $arg1 = $this->RDP_StackPop();
        if (is_null($arg1))
        {
            $this->RDP_AddError($this->gT("Invalid value(s) on the stack"), $token);
            return false;
        }
        // TODO:  try to determine datatype?
        switch($token[0])
        {
            case '+':
                $result = array((+$arg1[0]),$token[1],'NUMBER');
                break;
            case '-':
                $result = array((-$arg1[0]),$token[1],'NUMBER');
                break;
            case '!';
                $result = array((!$arg1[0]),$token[1],'NUMBER');
                break;
        }
        $this->RDP_StackPush($result);
        return true;
    }


    /**
     * Main entry function
     * @param <type> $expr
     * @param <type> $onlyparse - if true, then validate the syntax without computing an answer
     * @return boolean - true if success, false if any error occurred
     */

    public function RDP_Evaluate($expr, $onlyparse=false)
    {
        $this->RDP_expr = $expr;
        $this->RDP_tokens = $this->RDP_Tokenize($expr);
        $this->RDP_count = count($this->RDP_tokens);
        $this->RDP_pos = -1; // starting position within array (first act will be to increment it)
        $this->RDP_errs = array();
        $this->RDP_onlyparse = $onlyparse;
        $this->RDP_stack = array();
        $this->RDP_evalStatus = false;
        $this->RDP_result = NULL;
        $this->varsUsed = array();
        $this->jsExpression = NULL;

        if ($this->HasSyntaxErrors()) {
            return false;
        }
        elseif ($this->RDP_EvaluateExpressions())
        {
            if ($this->RDP_pos < $this->RDP_count)
            {
                $this->RDP_AddError($this->gT("Extra tokens found"), $this->RDP_tokens[$this->RDP_pos]);
                return false;
            }
            $this->RDP_result = $this->RDP_StackPop();
            if (is_null($this->RDP_result))
            {
                return false;
            }
            if (count($this->RDP_stack) == 0)
            {
                $this->RDP_evalStatus = true;
                return true;
            }
            else
            {
                $this-RDP_AddError($this->gT("Unbalanced equation - values left on stack"),NULL);
                return false;
            }
        }
        else
        {
            $this->RDP_AddError($this->gT("Not a valid expression"),NULL);
            return false;
        }
    }


    /**
     * Process "a op b" where op in (+,-,concatenate)
     * @return boolean - true if success, false if any error occurred
     */
    private function RDP_EvaluateAdditiveExpression()
    {
        if (!$this->RDP_EvaluateMultiplicativeExpression())
        {
            return false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            if ($token[2] == 'BINARYOP')
            {
                switch ($token[0])
                {
                    case '+':
                    case '-';
                        if ($this->RDP_EvaluateMultiplicativeExpression())
                        {
                            if (!$this->RDP_EvaluateBinary($token))
                            {
                                return false;
                            }
                            // else continue;
                        }
                        else
                        {
                            return false;
                        }
                        break;
                    default:
                        --$this->RDP_pos;
                        return true;
                }
            }
            else
            {
                --$this->RDP_pos;
                return true;
            }
        }
        return true;
    }

    /**
     * Process a Constant (number of string), retrieve the value of a known variable, or process a function, returning result on the stack.
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateConstantVarOrFunction()
    {
        if ($this->RDP_pos + 1 >= $this->RDP_count)
        {
             $this->RDP_AddError($this->gT("Poorly terminated expression - expected a constant or variable"), NULL);
             return false;
        }
        $token = $this->RDP_tokens[++$this->RDP_pos];
        switch ($token[2])
        {
            case 'NUMBER':
            case 'DQ_STRING':
            case 'SQ_STRING':
                $this->RDP_StackPush($token);
                return true;
                break;
            case 'WORD':
            case 'SGQA':
                if (($this->RDP_pos + 1) < $this->RDP_count and $this->RDP_tokens[($this->RDP_pos + 1)][2] == 'LP')
                {
                    return $this->RDP_EvaluateFunction();
                }
                else
                {
                    if ($this->RDP_isValidVariable($token[0]))
                    {
                        $this->varsUsed[] = $token[0];  // add this variable to list of those used in this equation
                        if (preg_match("/\.(gid|grelevance|gseq|jsName|mandatory|qid|qseq|question|readWrite|relevance|rowdivid|sgqa|type)$/",$token[0]))
                        {
                            $relStatus=1;   // static, so always relevant
                        }
                        else
                        {
                            $relStatus = $this->GetVarAttribute($token[0],'relevanceStatus',1);
                        }
                        if ($relStatus==1)
                        {
                            $result = array($this->GetVarAttribute($token[0],NULL,''),$token[1],'NUMBER');
                        }
                        else
                        {
                            $result = array(NULL,$token[1],'NUMBER');   // was 0 instead of NULL
                        }
                        $this->RDP_StackPush($result);

                        // TODO - currently, will try to process value anyway, but want to show a potential error.  Should it be a definitive error (e.g. prevent this behavior)?
                        $groupSeq = $this->GetVarAttribute($token[0],'gseq',-1);
                        if (($groupSeq != -1 && $this->groupSeq != -1) && ($groupSeq > $this->groupSeq))
                        {
                            $this->RDP_AddError($this->gT("This variable is not declared until a later page"),$token);
                            return false;
                        }
                        return true;
                    }
                    else
                    {
                        $this->RDP_AddError($this->gT("Undefined variable"), $token);
                        return false;
                    }
                }
                break;
            case 'COMMA':
                --$this->RDP_pos;
                $this->RDP_AddError($this->gT("Should never  get to this line?"),$token);
                return false;
            default:
                return false;
                break;
        }
    }

    /**
     * Process "a == b", "a eq b", "a != b", "a ne b"
     * @return boolean - true if success, false if any error occurred
     */
    private function RDP_EvaluateEqualityExpression()
    {
        if (!$this->RDP_EvaluateRelationExpression())
        {
            return false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            switch (strtolower($token[0]))
            {
                case '==':
                case 'eq':
                case '!=':
                case 'ne':
                    if ($this->RDP_EvaluateRelationExpression())
                    {
                        if (!$this->RDP_EvaluateBinary($token))
                        {
                            return false;
                        }
                        // else continue;
                    }
                    else
                    {
                        return false;
                    }
                    break;
                default:
                    --$this->RDP_pos;
                    return true;
            }
        }
        return true;
    }

    /**
     * Process a single expression (e.g. without commas)
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateExpression()
    {
        if ($this->RDP_pos + 2 < $this->RDP_count)
        {
            $token1 = $this->RDP_tokens[++$this->RDP_pos];
            $token2 = $this->RDP_tokens[++$this->RDP_pos];
            if ($token2[2] == 'ASSIGN')
            {
                if ($this->RDP_isValidVariable($token1[0]))
                {
                    $this->varsUsed[] = $token1[0];  // add this variable to list of those used in this equation
                    if ($this->RDP_isWritableVariable($token1[0]))
                    {
                        $evalStatus = $this->RDP_EvaluateLogicalOrExpression();
                        if ($evalStatus)
                        {
                            $result = $this->RDP_StackPop();
                            if (!is_null($result))
                            {
                                $newResult = $token2;
                                $newResult[2] = 'NUMBER';
                                $newResult[0] = $this->RDP_SetVariableValue($token2[0], $token1[0], $result[0]);
                                $this->RDP_StackPush($newResult);
                            }
                            else
                            {
                                $evalStatus = false;
                            }
                        }
                        return $evalStatus;
                    }
                    else
                    {
                        $this->RDP_AddError($this->gT('The value of this variable can not be changed'), $token1);
                        return false;
                    }
                }
                else
                {
                    $this->RDP_AddError($this->gT('Only variables can be assigned values'), $token1);
                    return false;
                }
            }
            else
            {
                // not an assignment expression, so try something else
                $this->RDP_pos -= 2;
                return $this->RDP_EvaluateLogicalOrExpression();
            }
        }
        else
        {
            return $this->RDP_EvaluateLogicalOrExpression();
        }
    }

    /**
     * Process "expression [, expression]*
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateExpressions()
    {
        $evalStatus = $this->RDP_EvaluateExpression();
        if (!$evalStatus)
        {
            return false;
        }

        while (++$this->RDP_pos < $this->RDP_count) {
            $token = $this->RDP_tokens[$this->RDP_pos];
            if ($token[2] == 'RP')
            {
                return true;    // presumbably the end of an expression
            }
            elseif ($token[2] == 'COMMA')
            {
                if ($this->RDP_EvaluateExpression())
                {
                    $secondResult = $this->RDP_StackPop();
                    $firstResult = $this->RDP_StackPop();
                    if (is_null($firstResult))
                    {
                        return false;
                    }
                    $this->RDP_StackPush($secondResult);
                    $evalStatus = true;
                }
                else
                {
                    return false;   // an error must have occurred
                }
            }
            else
            {
                $this->RDP_AddError($this->gT("Expected expressions separated by commas"),$token);
                $evalStatus = false;
                break;
            }
        }
        while (++$this->RDP_pos < $this->RDP_count)
        {
            $token = $this->RDP_tokens[$this->RDP_pos];
            $this->RDP_AddError($this->gT("Extra token found after Expressions"),$token);
            $evalStatus = false;
        }
        return $evalStatus;
    }

    /**
     * Process a function call
     * @return boolean - true if success, false if any error occurred
     */
    private function RDP_EvaluateFunction()
    {
        $funcNameToken = $this->RDP_tokens[$this->RDP_pos]; // note that don't need to increment position for functions
        $funcName = $funcNameToken[0];
        if (!$this->RDP_isValidFunction($funcName))
        {
            $this->RDP_AddError($this->gT("Undefined Function"), $funcNameToken);
            return false;
        }
        $token2 = $this->RDP_tokens[++$this->RDP_pos];
        if ($token2[2] != 'LP')
        {
            $this->RDP_AddError($this->gT("Expected left parentheses after function name"), $funcNameToken);
        }
        $params = array();  // will just store array of values, not tokens
        while ($this->RDP_pos + 1 < $this->RDP_count)
        {
            $token3 = $this->RDP_tokens[$this->RDP_pos + 1];
            if (count($params) > 0)
            {
                // should have COMMA or RP
                if ($token3[2] == 'COMMA')
                {
                    ++$this->RDP_pos;   // consume the token so can process next clause
                    if ($this->RDP_EvaluateExpression())
                    {
                        $value = $this->RDP_StackPop();
                        if (is_null($value))
                        {
                            return false;
                        }
                        $params[] = $value[0];
                        continue;
                    }
                    else
                    {
                        $this->RDP_AddError($this->gT("Extra comma found in function"), $token3);
                        return false;
                    }
                }
            }
            if ($token3[2] == 'RP')
            {
                ++$this->RDP_pos;   // consume the token so can process next clause
                return $this->RDP_RunFunction($funcNameToken,$params);
            }
            else
            {
                if ($this->RDP_EvaluateExpression())
                {
                    $value = $this->RDP_StackPop();
                    if (is_null($value))
                    {
                        return false;
                    }
                    $params[] = $value[0];
                    continue;
                }
                else
                {
                    return false;
                }
            }
        }
    }

    /**
     * Process "a && b" or "a and b"
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateLogicalAndExpression()
    {
        if (!$this->RDP_EvaluateEqualityExpression())
        {
            return false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            switch (strtolower($token[0]))
            {
                case '&&':
                case 'and':
                    if ($this->RDP_EvaluateEqualityExpression())
                    {
                        if (!$this->RDP_EvaluateBinary($token))
                        {
                            return false;
                        }
                        // else continue
                    }
                    else
                    {
                        return false;   // an error must have occurred
                    }
                    break;
                default:
                    --$this->RDP_pos;
                    return true;
            }
        }
        return true;
    }

    /**
     * Process "a || b" or "a or b"
     * @return boolean - true if success, false if any error occurred
     */
    private function RDP_EvaluateLogicalOrExpression()
    {
        if (!$this->RDP_EvaluateLogicalAndExpression())
        {
            return false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            switch (strtolower($token[0]))
            {
                case '||':
                case 'or':
                    if ($this->RDP_EvaluateLogicalAndExpression())
                    {
                        if (!$this->RDP_EvaluateBinary($token))
                        {
                            return false;
                        }
                        // else  continue
                    }
                    else
                    {
                        // an error must have occurred
                        return false;
                    }
                    break;
                default:
                    // no more expressions being  ORed together, so continue parsing
                    --$this->RDP_pos;
                    return true;
            }
        }
        // no more tokens to parse
        return true;
    }

    /**
     * Process "a op b" where op in (*,/)
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateMultiplicativeExpression()
    {
        if (!$this->RDP_EvaluateUnaryExpression())
        {
            return  false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            if ($token[2] == 'BINARYOP')
            {
                switch ($token[0])
                {
                    case '*':
                    case '/';
                        if ($this->RDP_EvaluateUnaryExpression())
                        {
                            if (!$this->RDP_EvaluateBinary($token))
                            {
                                return false;
                            }
                            // else  continue
                        }
                        else
                        {
                            // an error must have occurred
                            return false;
                        }
                        break;
                        break;
                    default:
                        --$this->RDP_pos;
                        return true;
                }
            }
            else
            {
                --$this->RDP_pos;
                return true;
            }
        }
        return true;
    }

    /**
     * Process expressions including functions and parenthesized blocks
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluatePrimaryExpression()
    {
        if (($this->RDP_pos + 1) >= $this->RDP_count) {
            $this->RDP_AddError($this->gT("Poorly terminated expression - expected a constant or variable"), NULL);
            return false;
        }
        $token = $this->RDP_tokens[++$this->RDP_pos];
        if ($token[2] == 'LP')
        {
            if (!$this->RDP_EvaluateExpressions())
            {
                return false;
            }
            $token = $this->RDP_tokens[$this->RDP_pos];
            if ($token[2] == 'RP')
            {
                return true;
            }
            else
            {
                $this->RDP_AddError($this->gT("Expected right parentheses"), $token);
                return false;
            }
        }
        else
        {
            --$this->RDP_pos;
            return $this->RDP_EvaluateConstantVarOrFunction();
        }
    }

    /**
     * Process "a op b" where op in (lt, gt, le, ge, <, >, <=, >=)
     * @return boolean - true if success, false if any error occurred
     */
    private function RDP_EvaluateRelationExpression()
    {
        if (!$this->RDP_EvaluateAdditiveExpression())
        {
            return false;
        }
        while (($this->RDP_pos + 1) < $this->RDP_count)
        {
            $token = $this->RDP_tokens[++$this->RDP_pos];
            switch (strtolower($token[0]))
            {
                case '<':
                case 'lt':
                case '<=';
                case 'le':
                case '>':
                case 'gt':
                case '>=';
                case 'ge':
                    if ($this->RDP_EvaluateAdditiveExpression())
                    {
                        if (!$this->RDP_EvaluateBinary($token))
                        {
                            return false;
                        }
                        // else  continue
                    }
                    else
                    {
                        // an error must have occurred
                        return false;
                    }
                    break;
                default:
                    --$this->RDP_pos;
                    return true;
            }
        }
        return true;
    }

    /**
     * Process "op a" where op in (+,-,!)
     * @return boolean - true if success, false if any error occurred
     */

    private function RDP_EvaluateUnaryExpression()
    {
        if (($this->RDP_pos + 1) >= $this->RDP_count) {
            $this->RDP_AddError($this->gT("Poorly terminated expression - expected a constant or variable"), NULL);
            return false;
        }
        $token = $this->RDP_tokens[++$this->RDP_pos];
        if ($token[2] == 'NOT' || $token[2] == 'BINARYOP')
        {
            switch ($token[0])
            {
                case '+':
                case '-':
                case '!':
                    if (!$this->RDP_EvaluatePrimaryExpression())
                    {
                        return false;
                    }
                    return $this->RDP_EvaluateUnary($token);
                    break;
                default:
                    --$this->RDP_pos;
                    return $this->RDP_EvaluatePrimaryExpression();
            }
        }
        else
        {
            --$this->RDP_pos;
            return $this->RDP_EvaluatePrimaryExpression();
        }
    }

    /**
     * Returns array of all JavaScript-equivalent variable names used when parsing a string via sProcessStringContainingExpressions
     * @return <type>
     */
    public function GetAllJsVarsUsed()
    {
        if (is_null($this->allVarsUsed)){
            return array();
        }
        $names = array_unique($this->allVarsUsed);
        if (is_null($names)) {
            return array();
        }
        $jsNames = array();
        foreach ($names as $name)
        {
            if (preg_match("/\.(gid|grelevance|gseq|jsName|mandatory|qid|qseq|question|readWrite|relevance|rowdivid|sgqa|type)$/",$name))
            {
                continue;
            }
            $val = $this->GetVarAttribute($name,'jsName','');
            if ($val != '') {
                $jsNames[] = $val;
            }
        }
        return array_unique($jsNames);
    }

    /**
     * Return the list of all of the JavaScript variables used by the most recent expression - only those that are set on the current page
     * This is used to control static vs dynamic substitution.  If an expression is entirely made up of off-page changes, it can be statically replaced.
     * @return <type>
     */
    public function GetOnPageJsVarsUsed()
    {
        if (is_null($this->varsUsed)){
            return array();
        }
        if ($this->surveyMode=='survey')
        {
            return $this->GetJsVarsUsed();
        }
        $names = array_unique($this->varsUsed);
        if (is_null($names)) {
            return array();
        }
        $jsNames = array();
        foreach ($names as $name)
        {
            if (preg_match("/\.(gid|grelevance|gseq|jsName|mandatory|qid|qseq|question|readWrite|relevance|rowdivid|sgqa|type)$/",$name))
            {
                continue;
            }
            $val = $this->GetVarAttribute($name,'jsName','');
            switch ($this->surveyMode)
            {
                case 'group':
                    $gseq = $this->GetVarAttribute($name,'gseq','');
                    $onpage = ($gseq == $this->groupSeq);
                    break;
                case 'question':
                    $qseq = $this->GetVarAttribute($name,'qseq','');
                    $onpage = ($qseq == $this->questionSeq);
                    break;
                case 'survey':
                    $onpage = true;
                    break;
            }
            if ($val != '' && $onpage) {
                $jsNames[] = $val;
            }
        }
        return array_unique($jsNames);
    }

    /**
     * Return the list of all of the JavaScript variables used by the most recent expression
     * @return <type>
     */
    public function GetJsVarsUsed()
    {
        if (is_null($this->varsUsed)){
            return array();
        }
        $names = array_unique($this->varsUsed);
        if (is_null($names)) {
            return array();
        }
        $jsNames = array();
        foreach ($names as $name)
        {
            if (preg_match("/\.(gid|grelevance|gseq|jsName|mandatory|qid|qseq|question|readWrite|relevance|rowdivid|sgqa|type)$/",$name))
            {
                continue;
            }
            $val = $this->GetVarAttribute($name,'jsName','');
            if ($val != '') {
                $jsNames[] = $val;
            }
        }
        return array_unique($jsNames);
    }

    /**
     * Return the JavaScript variable name for a named variable
     * @param <type> $name
     * @return <type>
     */
    public function GetJsVarFor($name)
    {
        return $this->GetVarAttribute($name,'jsName','');
    }

    /**
     * Returns array of all variables used when parsing a string via sProcessStringContainingExpressions
     * @return <type>
     */
    public function GetAllVarsUsed()
    {
        return array_unique($this->allVarsUsed);
    }

    /**
     * Return the result of evaluating the equation - NULL if  error
     * @return mixed
     */
    public function GetResult()
    {
        return $this->RDP_result[0];
    }

    /**
     * Return an array of errors
     * @return array
     */
    public function GetErrors()
    {
        return $this->RDP_errs;
    }

    /**
     * Converts the most recent expression into a valid JavaScript expression, mapping function and variable names and operators as needed.
     * @return <type> the JavaScript expresssion
     */
    public function GetJavaScriptEquivalentOfExpression()
    {
        if (!is_null($this->jsExpression))
        {
            return $this->jsExpression;
        }
        if ($this->HasErrors())
        {
            $this->jsExpression = '';
            return '';
        }
        $tokens = $this->RDP_tokens;
        $stringParts=array();
        $numTokens = count($tokens);
        for ($i=0;$i<$numTokens;++$i)
        {
            $token = $tokens[$i];
            // When do these need to be quoted?

            switch ($token[2])
            {
                case 'DQ_STRING':
                    $stringParts[] = '"' . addcslashes($token[0],'\"') . '"'; // htmlspecialchars($token[0],ENT_QUOTES,'UTF-8',false) . "'";
                    break;
                case 'SQ_STRING':
                    $stringParts[] = "'" . addcslashes($token[0],"\'") . "'"; // htmlspecialchars($token[0],ENT_QUOTES,'UTF-8',false) . "'";
                    break;
                case 'SGQA':
                case 'WORD':
                    if ($i+1<$numTokens && $tokens[$i+1][2] == 'LP')
                    {
                        // then word is a function name
                        $funcInfo = $this->RDP_ValidFunctions[$token[0]];
                        if ($funcInfo[1] == 'NA')
                        {
                            return '';  // to indicate that this is trying to use a undefined function.  Need more graceful solution
                        }
                        $stringParts[] = $funcInfo[1];  // the PHP function name
                    }
                    elseif ($i+1<$numTokens && $tokens[$i+1][2] == 'ASSIGN')
                    {
                        $jsName = $this->GetVarAttribute($token[0],'jsName','');
                        $stringParts[] = "document.getElementById('" . $jsName . "').value";
                        if ($tokens[$i+1][0] == '+=')
                        {
                            // Javascript does concatenation unless both left and right side are numbers, so refactor the equation
                            $varName = $this->GetVarAttribute($token[0],'varName',$token[0]);
                            $stringParts[] = " = LEMval('" . $varName . "') + ";
                            ++$i;
                        }
                    }
                    else
                    {
                        $jsName = $this->GetVarAttribute($token[0],'jsName','');
                        $code = $this->GetVarAttribute($token[0],'code','');
                        if ($jsName != '')
                        {
                            $varName = $this->GetVarAttribute($token[0],'varName',$token[0]);
                            $stringParts[] = "LEMval('" . $varName . "') ";
                        }
                        else
                        {
                            $stringParts[] = is_numeric($code) ? $code : ("'" . addcslashes($code,"'") . "'"); // htmlspecialchars($code,ENT_QUOTES,'UTF-8',false) . "'");
                        }
                    }
                    break;
                case 'LP':
                case 'RP':
                    $stringParts[] = $token[0];
                    break;
                case 'NUMBER':
                    $stringParts[] = is_numeric($token[0]) ? $token[0] : ("'" . $token[0] . "'");
                    break;
                case 'COMMA':
                    $stringParts[] = $token[0] . ' ';
                    break;
                default:
                    // don't need to check type of $token[2] here since already handling SQ_STRING and DQ_STRING above
                    switch (strtolower($token[0]))
                    {
                        case 'and': $stringParts[] = ' && '; break;
                        case 'or':  $stringParts[] = ' || '; break;
                        case 'lt':  $stringParts[] = ' < '; break;
                        case 'le':  $stringParts[] = ' <= '; break;
                        case 'gt':  $stringParts[] = ' > '; break;
                        case 'ge':  $stringParts[] = ' >= '; break;
                        case 'eq':  case '==': $stringParts[] = ' == '; break;
                        case 'ne':  case '!=': $stringParts[] = ' != '; break;
                        default:    $stringParts[] = ' ' . $token[0] . ' '; break;
                    }
                    break;
            }
        }
        // for each variable that does not have a default value, add clause to throw error if any of them are NA
        $nonNAvarsUsed = array();
        foreach ($this->GetVarsUsed() as $var)    // this function wants to see the NAOK suffix
        {
            if (!preg_match("/^.*\.(NAOK|relevanceStatus)$/", $var))
            {
                if ($this->GetVarAttribute($var,'jsName','') != '')
                {
                    $nonNAvarsUsed[] = $var;
                }
            }
        }
        $mainClause = implode('', $stringParts);
        $varsUsed = implode("', '", $nonNAvarsUsed);
        if ($varsUsed != '')
        {
            $this->jsExpression = "LEMif(LEManyNA('" . $varsUsed . "'),'',(" . $mainClause . "))";
        }
        else
        {
            $this->jsExpression = '(' . $mainClause . ')';
        }
        return $this->jsExpression;
    }

    /**
     * JavaScript Test function - simply writes the result of the current JavaScriptEquivalentFunction to the output buffer.
     * @return <type>
     */
    public function GetJavascriptTestforExpression($expected,$num)
    {
        // assumes that the hidden variables have already been declared
        $expr = $this->GetJavaScriptEquivalentOfExpression();
        if (is_null($expr) || $expr == '') {
            $expr = "'NULL'";
        }
        $jsmultiline_expr = str_replace("\n","\\\n",$expr);
        $jsmultiline_expected = str_replace("\n","\\\n",addslashes($expected));
        $jsParts = array();
        $jsParts[] = "val = " . $jsmultiline_expr . ";\n";
        $jsParts[] = "klass = (LEMeq(addslashes(val),'" . $jsmultiline_expected . "')) ? 'ok' : 'error';\n";
        $jsParts[] = "document.getElementById('test_" . $num . "').innerHTML=(val);\n";
        $jsParts[] = "document.getElementById('test_" . $num . "').className=klass;\n";
        return implode('',$jsParts);

    }

    /**
     * Generate the function needed to dynamically change the value of a <span> section
     * @param <type> $name - the ID name for the function
     * @return <type>
     */
    public function GetJavaScriptFunctionForReplacement($questionNum, $name,$eqn)
    {
        $jsParts = array();
        $jsParts[] = "  try{\n";
        $jsParts[] = "  document.getElementById('" . $name . "').innerHTML=LEMfixnum(\n    ";
        $jsParts[] = $this->GetJavaScriptEquivalentOfExpression();
        $jsParts[] = ");\n";
        $jsParts[] = "  } catch (e) { }\n";
        return implode('',$jsParts);
    }

    /**
     * Returns the most recent PrettyPrint string generated by sProcessStringContainingExpressions
     */
    public function GetLastPrettyPrintExpression()
    {
        return $this->prettyPrintSource;
    }

    /**
     * This is only used when there are no needed substitutions
     * @param <type> $expr
     */
    public function SetPrettyPrintSource($expr)
    {
        $this->prettyPrintSource = $expr;
    }

    /**
     * Color-codes Expressions (using HTML <span> tags), showing variable types and values.
     * @return <type>
     */
    public function GetPrettyPrintString()
    {
        // color code the equation, showing not only errors, but also variable attributes
        $errs = $this->RDP_errs;
        $tokens = $this->RDP_tokens;
        $errCount = count($errs);
        $errIndex = 0;
        if ($errCount > 0)
        {
            usort($errs,"cmpErrorTokens");
        }
        $errSpecificStyle= "style='border-style: solid; border-width: 2px; border-color: red;'";
        $stringParts=array();
        $numTokens = count($tokens);
        $globalErrs=array();
        while ($errIndex < $errCount)
        {
            if ($errs[$errIndex++][1][1]==0)
            {
                // General message, associated with position 0
                $globalErrs[] = $errs[$errIndex-1][0];
            }
            else
            {
                --$errIndex;
                break;
            }
        }
        for ($i=0;$i<$numTokens;++$i)
        {
            $token = $tokens[$i];
            $messages=array();
            $thisTokenHasError=false;
            if ($i==0 && count($globalErrs) > 0)
            {
                $messages = array_merge($messages,$globalErrs);
                $thisTokenHasError=true;
            }
            if ($errIndex < $errCount && $token[1] == $errs[$errIndex][1][1])
            {
                $messages[] = $errs[$errIndex][0];
                $thisTokenHasError=true;
            }
            if ($thisTokenHasError)
            {
                $stringParts[] = "<span title='" . implode('; ',$messages) . "' " . $errSpecificStyle . ">";
            }
            switch ($token[2])
            {
                case 'DQ_STRING':
                    $stringParts[] = "<span title='" . implode('; ',$messages) . "' style='color: gray'>\"";
                    $stringParts[] = $token[0]; // htmlspecialchars($token[0],ENT_QUOTES,'UTF-8',false);
                    $stringParts[] = "\"</span>";
                    break;
                case 'SQ_STRING':
                    $stringParts[] = "<span title='" . implode('; ',$messages) . "' style='color: gray'>'";
                    $stringParts[] = $token[0]; // htmlspecialchars($token[0],ENT_QUOTES,'UTF-8',false);
                    $stringParts[] = "'</span>";
                    break;
                case 'SGQA':
                case 'WORD':
                    if ($i+1<$numTokens && $tokens[$i+1][2] == 'LP')
                    {
                        // then word is a function name
                        if ($this->RDP_isValidFunction($token[0])) {
                            $funcInfo = $this->RDP_ValidFunctions[$token[0]];
                            $messages[] = $funcInfo[2];
                            $messages[] = $funcInfo[3];
                        }
                        $stringParts[] = "<span title='" . implode('; ',$messages) . "' style='color: blue; font-weight: bold'>";
                        $stringParts[] = $token[0];
                        $stringParts[] = "</span>";
                    }
                    else
                    {
                        if (!$this->RDP_isValidVariable($token[0]))
                        {
                            $color = 'red';
                            $displayName = $token[0];
                        }
                        else
                        {
                            $jsName = $this->GetVarAttribute($token[0],'jsName','');
                            $code = $this->GetVarAttribute($token[0],'code','');
                            $question = $this->GetVarAttribute($token[0], 'question', '');
                            $qcode= $this->GetVarAttribute($token[0],'qcode','');
                            $questionSeq = $this->GetVarAttribute($token[0],'qseq',-1);
                            $groupSeq = $this->GetVarAttribute($token[0],'gseq',-1);
                            $ansList = $this->GetVarAttribute($token[0],'ansList','');
                            $gid = $this->GetVarAttribute($token[0],'gid',-1);
                            $qid = $this->GetVarAttribute($token[0],'qid',-1);

                            if ($jsName != '') {
                                $descriptor = '[' . $jsName . ']';
                            }
                            else {
                                $descriptor = '';
                            }
                            // Show variable name instead of SGQA code, if available
                            if ($qcode != '') {
                                if (preg_match('/^INSERTANS:/',$token[0])) {
                                    $displayName = $qcode . '.shown';
                                    $descriptor = '[' . $token[0] . ']';
                                }
                                else {
                                    $args = explode('.',$token[0]);
                                    if (count($args) == 2) {
                                        $displayName = $qcode . '.' . $args[1];
                                    }
                                    else {
                                        $displayName = $qcode;
                                    }
                                }
                            }
                            else {
                                $displayName = $token[0];
                            }
                            if ($questionSeq != -1) {
                                $descriptor .= '[G:' . $groupSeq . ']';
                            }
                            if ($groupSeq != -1) {
                                $descriptor .= '[Q:' . $questionSeq . ']';
                            }
                            if (strlen($descriptor) > 0) {
                                $descriptor .= ': ';
                            }

                            if (version_compare(phpversion(), "5.2.3")>=0)
                            {
                                // 4th parameter to htmlspecialchars only became available in PHP version 5.2.3
                                $messages[] = $descriptor . htmlspecialchars($question,ENT_QUOTES,'UTF-8',false);
                                if ($ansList != '')
                                {
                                    $messages[] = htmlspecialchars($ansList,ENT_QUOTES,'UTF-8',false);
                                }
                                if ($code != '') {
                                    if ($token[2] == 'SGQA' && preg_match('/^INSERTANS:/',$token[0])) {
                                        $shown = $this->GetVarAttribute($token[0], 'shown', '');
                                        $messages[] = 'value=[' . htmlspecialchars($code,ENT_QUOTES,'UTF-8',false) . '] '
                                                . htmlspecialchars($shown,ENT_QUOTES,'UTF-8',false);
                                    }
                                    else {
                                        $messages[] = 'value=' . htmlspecialchars($code,ENT_QUOTES,'UTF-8',false);
                                    }
                                }
                            }
                            else
                            {
                                $messages[] = $descriptor . htmlspecialchars($question,ENT_QUOTES,'UTF-8');
                                if ($ansList != '')
                                {
                                    $messages[] = htmlspecialchars($ansList,ENT_QUOTES,'UTF-8');
                                }
                                if ($code != '') {
                                    if ($token[2] == 'SGQA' && preg_match('/^INSERTANS:/',$token[0])) {
                                        $shown = $this->GetVarAttribute($token[0], 'shown', '');
                                        $messages[] = 'value=[' . htmlspecialchars($code,ENT_QUOTES,'UTF-8') . '] '
                                                . htmlspecialchars($shown,ENT_QUOTES,'UTF-8');
                                    }
                                    else {
                                        $messages[] = 'value=' . htmlspecialchars($code,ENT_QUOTES,'UTF-8');
                                    }
                                }
                            }
                            if ($this->groupSeq == -1 || $groupSeq == -1 || $questionSeq == -1 || $this->questionSeq == -1) {
                                $color = '#996600'; // tan
                            }
                            else if ($groupSeq > $this->groupSeq) {
                                $color = '#FF00FF ';     // pink a likely error
                            }
                            else if ($groupSeq < $this->groupSeq) {
                                $color = 'green';
                            }
                            else if ($questionSeq > $this->questionSeq) {
                                $color = 'maroon';  // #228b22 - warning
                            }
                            else {
                                $color = '#4C88BE';    // cyan that goes well with the background color
                            }
                        }
                        // prevent EM prcessing of messages within span
                        $message = implode('; ',$messages);
                        $message = str_replace(array('{','}'), array('{ ', ' }'), $message);

                        $stringParts[] = "<span title='"  . $message . "' style='color: ". $color . "; font-weight: bold'";
                        if ($this->hyperlinkSyntaxHighlighting && isset($gid) && isset($qid)) {
                            // Modify this link to utilize a different framework
                            $editlink = $this->rooturl . '/admin/admin.php?sid=' . $this->sid . '&gid=' . $gid . '&qid=' . $qid;
                            $stringParts[] = " onclick='window.open(\"" . $editlink . "\");'";
                        }
                        $stringParts[] = ">";
                        if ($this->sgqaNaming)
                        {
                            $sgqa = substr($jsName,4);
                            $nameParts = explode('.',$displayName);
                            if (count($nameParts)==2)
                            {
                                $sgqa .= '.' . $nameParts[1];
                            }
                            $stringParts[] = $sgqa;
                        }
                        else
                        {
                            $stringParts[] = $displayName;
                        }
                        $stringParts[] = "</span>";
                    }
                    break;
                case 'ASSIGN':
                    $messages[] = 'Assigning a new value to a variable';
                    $stringParts[] = "<span title='" . implode('; ',$messages) . "' style='color: red; font-weight: bold'>";
                    $stringParts[] = $token[0];
                    $stringParts[] =  "</span>";
                    break;
                case 'COMMA':
                    $stringParts[] = $token[0] . ' ';
                    break;
                case 'LP':
                case 'RP':
                case 'NUMBER':
                    $stringParts[] = $token[0];
                    break;
                default:
                    $stringParts[] = ' ' . $token[0] . ' ';
                    break;
            }
            if ($thisTokenHasError)
            {
                $stringParts[] = "</span>";
                ++$errIndex;
            }
        }
        return "<span style='background-color: #eee8aa;'>" . implode('', $stringParts) . "</span>";
    }

    /**
     * Get information about the variable, including JavaScript name, read-write status, and whether set on current page.
     * @param <type> $varname
     * @return <type>
     */
    private function GetVarAttribute($name,$attr,$default)
    {
        return LimeExpressionManager::GetVarAttribute($name,$attr,$default,$this->groupSeq,$this->questionSeq);
    }

    /**
     * Return array of the list of variables used  in the equation
     * @return array
     */
    public function GetVarsUsed()
    {
        return array_unique($this->varsUsed);
    }

    /**
     * Return true if there were syntax or processing errors
     * @return boolean
     */
    public function HasErrors()
    {
        return (count($this->RDP_errs) > 0);
    }

    /**
     * Return true if there are syntax errors
     * @return boolean
     */
    private function HasSyntaxErrors()
    {
        // check for bad tokens
        // check for unmatched parentheses
        // check for undefined variables
        // check for undefined functions (but can't easily check allowable # elements?)

        $nesting = 0;

        for ($i=0;$i<$this->RDP_count;++$i)
        {
            $token = $this->RDP_tokens[$i];
            switch ($token[2])
            {
                case 'LP':
                    ++$nesting;
                    break;
                case 'RP':
                    --$nesting;
                    if ($nesting < 0)
                    {
                        $this->RDP_AddError($this->gT("Extra right parentheses detected"), $token);
                    }
                    break;
                case 'WORD':
                case 'SGQA':
                    if ($i+1 < $this->RDP_count and $this->RDP_tokens[$i+1][2] == 'LP')
                    {
                        if (!$this->RDP_isValidFunction($token[0]))
                        {
                            $this->RDP_AddError($this->gT("Undefined function"), $token);
                        }
                    }
                    else
                    {
                        if (!($this->RDP_isValidVariable($token[0])))
                        {
                            $this->RDP_AddError($this->gT("Undefined variable"), $token);
                        }
                    }
                    break;
                case 'OTHER':
                    $this->RDP_AddError($this->gT("Unsupported syntax"), $token);
                    break;
                default:
                    break;
            }
        }
        if ($nesting != 0)
        {
            $this->RDP_AddError(sprintf($this->gT("Missing %s closing right parentheses"),$nesting),NULL);
        }
        return (count($this->RDP_errs) > 0);
    }

    /**
     * Return true if the function name is registered
     * @param <type> $name
     * @return boolean
     */

    private function RDP_isValidFunction($name)
    {
        return array_key_exists($name,$this->RDP_ValidFunctions);
    }

    /**
     * Return true if the variable name is registered
     * @param <type> $name
     * @return boolean
     */
    private function RDP_isValidVariable($name)
    {
        $varName = preg_replace("/^(?:INSERTANS:)?(.*?)(?:\.(?:" . ExpressionManager::$RDP_regex_var_attr . "))?$/", "$1", $name);
        return LimeExpressionManager::isValidVariable($varName);
    }

    /**
     * Return true if the variable name is writable
     * @param <type> $name
     * @return <type>
     */
    private function RDP_isWritableVariable($name)
    {
        return ($this->GetVarAttribute($name, 'readWrite', 'N') == 'Y');
    }

    /**
     * Process an expression and return its boolean value
     * @param <type> $expr
     * @param <type> $groupSeq - needed to determine whether using variables before they are declared
     * @param <type> $questionSeq - needed to determine whether using variables before they are declared
     * @return <type>
     */
    public function ProcessBooleanExpression($expr,$groupSeq=-1,$questionSeq=-1)
    {
        $this->groupSeq = $groupSeq;
        $this->questionSeq = $questionSeq;

        $expr = $this->ExpandThisVar($expr);
        $status = $this->RDP_Evaluate($expr);
        if (!$status) {
            return false;    // if there are errors in the expression, hide it?
        }
        $result = $this->GetResult();
        if (is_null($result)) {
            return false;    // if there are errors in the expression, hide it?
        }

        // Check whether any variables are irrelevant - making this comparable to JavaScript which uses LEManyNA(varlist) to do the same thing
        foreach ($this->GetVarsUsed() as $var)    // this function wants to see the NAOK suffix
        {
            if (!preg_match("/^.*\.(NAOK|relevanceStatus)$/", $var))
            {
                if (!LimeExpressionManager::GetVarAttribute($var,'relevanceStatus',false,$groupSeq,$questionSeq))
                {
                    return false;
                }
            }
        }
        return (boolean) $result;
    }

    /**
     * Start processing a group of substitions - will be incrementally numbered
     *
     * @param int $sid
     * @param string $rooturl
     * @param boolean $hyperlinkSyntaxHighlighting
     * @param string $surveyMode survey|group|question
     */
    public function StartProcessingGroup($sid=NULL,$rooturl='',$hyperlinkSyntaxHighlighting=false)
    {
        $this->substitutionNum=0;
        $this->substitutionInfo=array(); // array of JavaScripts for managing each substitution
        $this->sid=$sid;
        $this->rooturl=$rooturl;
        $this->hyperlinkSyntaxHighlighting=$hyperlinkSyntaxHighlighting;
    }

    /**
     * Clear cache of tailoring content.
     * When re-displaying same page, need to avoid generating double the amount of tailoring content.
     */
    public function ClearSubstitutionInfo()
    {
        $this->substitutionNum=0;
        $this->substitutionInfo=array(); // array of JavaScripts for managing each substitution
    }

    /**
     * Process multiple substitution iterations of a full string, containing multiple expressions delimited by {}, return a consolidated string
     * @param <type> $src
     * @param <type> $questionNum
     * @param <type> $numRecursionLevels - number of levels of recursive substitution to perform
     * @param <type> $whichPrettyPrintIteration - if recursing, specify which pretty-print iteration is desired
     * @param <type> $groupSeq - needed to determine whether using variables before they are declared
     * @param <type> $questionSeq - needed to determine whether using variables before they are declared
     * @return <type>
     */

    public function sProcessStringContainingExpressions($src, $questionNum=0, $numRecursionLevels=1, $whichPrettyPrintIteration=1, $groupSeq=-1, $questionSeq=-1, $staticReplacement=false)
    {
        // tokenize string by the {} pattern, properly dealing with strings in quotations, and escaped curly brace values
        $this->allVarsUsed = array();
        $this->questionSeq = $questionSeq;
        $this->groupSeq = $groupSeq;
        $result = $src;
        $prettyPrint = '';
        $errors = array();

        for($i=1;$i<=$numRecursionLevels;++$i)
        {
            // TODO - Since want to use <span> for dynamic substitution, what if there are recursive substititons?
            $result = $this->sProcessStringContainingExpressionsHelper($result,$questionNum, $staticReplacement);
            if ($i == $whichPrettyPrintIteration)
            {
                $prettyPrint = $this->prettyPrintSource;
            }
            $errors = array_merge($errors, $this->RDP_errs);
        }
        $this->prettyPrintSource = $prettyPrint;    // ensure that if doing recursive substition, can get original source to pretty print
        $result = str_replace(array('\{', '\}',), array('{', '}'), $result);
        $this->RDP_errs = $errors;
        return $result;
    }

    /**
     * Process one substitution iteration of a full string, containing multiple expressions delimited by {}, return a consolidated string
     * @param <type> $src
     * @param <type> $questionNum - used to generate substitution <span>s that indicate to which question they belong
     * @return <type>
     */

    public function sProcessStringContainingExpressionsHelper($src, $questionNum, $staticReplacement=false)
    {
        // tokenize string by the {} pattern, properly dealing with strings in quotations, and escaped curly brace values
        $stringParts = $this->asSplitStringOnExpressions($src);

        $resolvedParts = array();
        $prettyPrintParts = array();
        $allErrors=array();

        foreach ($stringParts as $stringPart)
        {
            if ($stringPart[2] == 'STRING') {
                $resolvedParts[] =  $stringPart[0];
                $prettyPrintParts[] = $stringPart[0];
            }
            else {
                ++$this->substitutionNum;
                $expr = $this->ExpandThisVar(substr($stringPart[0],1,-1));
                if ($this->RDP_Evaluate($expr))
                {
                    $resolvedPart = $this->GetResult();
                }
                else
                {
                    // show original and errors in-line
                    $resolvedPart = $this->GetPrettyPrintString();
                    $allErrors[] = $this->GetErrors();
                }
                $onpageJsVarsUsed = $this->GetOnPageJsVarsUsed();
                $jsVarsUsed = $this->GetJsVarsUsed();
                $prettyPrintParts[] = $this->GetPrettyPrintString();
                $this->allVarsUsed = array_merge($this->allVarsUsed,$this->GetVarsUsed());

                if (count($onpageJsVarsUsed) > 0 && !$staticReplacement)
                {
                    $idName = "LEMtailor_Q_" . $questionNum . "_" . $this->substitutionNum;
                    $resolvedParts[] = "<span id='" . $idName . "'>" . $resolvedPart . "</span>";
                    $this->substitutionVars[$idName] = 1;
                    $this->substitutionInfo[] = array(
                        'questionNum' => $questionNum,
                        'num' => $this->substitutionNum,
                        'id' => $idName,
                        'raw' => $stringPart[0],
                        'result' => $resolvedPart,
                        'vars' => implode('|',$jsVarsUsed),
                        'js' => $this->GetJavaScriptFunctionForReplacement($questionNum, $idName, $expr),
                    );
                }
                else
                {
                    $resolvedParts[] = $resolvedPart;
                }
            }
        }
        $result = implode('',$this->flatten_array($resolvedParts));
        $this->prettyPrintSource = implode('',$this->flatten_array($prettyPrintParts));
        $this->RDP_errs = $allErrors;   // so that has all errors from this string
        return $result;    // recurse in case there are nested ones, avoiding infinite loops?
    }

    /**
     * If the equation contains refernece to this, expand to comma separated list if needed.
     * @param type $eqn
     */
    function ExpandThisVar($src)
    {
        $splitter = '(?:\b(?:self|that))(?:\.(?:[A-Z0-9_]+))*';
        $parts = preg_split("/(" . $splitter . ")/i",$src,-1,(PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
        $result = '';
        foreach ($parts as $part)
        {
            if (preg_match("/" . $splitter . "/",$part))
            {
                $result .= LimeExpressionManager::GetAllVarNamesForQ($this->questionSeq,$part);
            }
            else
            {
                $result .= $part;
            }
        }

        return $result;
    }

    /**
     * Get info about all <span> elements needed for dynamic tailoring
     * @return <type>
     */
    public function GetCurrentSubstitutionInfo()
    {
        return $this->substitutionInfo;
    }

    /**
     * Flatten out an array, keeping it in the proper order
     * @param array $a
     * @return array
     */

    private function flatten_array(array $a) {
        $i = 0;
        while ($i < count($a)) {
            if (is_array($a[$i])) {
                array_splice($a, $i, 1, $a[$i]);
            } else {
                $i++;
            }
        }
        return $a;
    }


    /**
     * Run a registered function
     * Some PHP functions require specific data types - those can be cast here.
     * @param <type> $funcNameToken
     * @param <type> $params
     * @return boolean
     */
    private function RDP_RunFunction($funcNameToken,$params)
    {
        $name = $funcNameToken[0];
        if (!$this->RDP_isValidFunction($name))
        {
            return false;
        }
        $func = $this->RDP_ValidFunctions[$name];
        $funcName = $func[0];
        $numArgs = count($params);
        $result=1;  // default value for $this->RDP_onlyparse

        if (function_exists($funcName)) {
            $numArgsAllowed = array_slice($func, 5);    // get array of allowable argument counts from end of $func
            $argsPassed = is_array($params) ? count($params) : 0;

            // for unlimited #  parameters (any value less than 0).
            try
            {
                if ($numArgsAllowed[0] < 0) {
                    $minArgs = abs($numArgsAllowed[0] + 1); // so if value is -2, means that requires at least one argument
                    if ($argsPassed < $minArgs)
                    {
                        $this->RDP_AddError(sprintf($this->gT("Function must have at least %s argument(s)"), $minArgs), $funcNameToken);
                        return false;
                    }
                    if (!$this->RDP_onlyparse) {
                        switch($funcName) {
                            case 'sprintf':
                                // PHP doesn't let you pass array of parameters to sprintf, so must use call_user_func_array
                                $result = call_user_func_array('sprintf',$params);
                                break;
                            default:
                                $result = $funcName($params);
                                break;
                        }
                    }
                // Call  function with the params passed
                } elseif (in_array($argsPassed, $numArgsAllowed)) {

                    switch ($argsPassed) {
                    case 0:
                        if (!$this->RDP_onlyparse) {
                            $result = $funcName();
                        }
                        break;
                    case 1:
                        if (!$this->RDP_onlyparse) {
                            switch($funcName) {
                                case 'acos':
                                case 'asin':
                                case 'atan':
                                case 'cos':
                                case 'exp':
                                case 'is_nan':
                                case 'log':
                                case 'sin':
                                case 'sqrt':
                                case 'tan':
                                    if (is_numeric($params[0]))
                                    {
                                        $result = $funcName(floatval($params[0]));
                                    }
                                    else
                                    {
                                        $result = NAN;
                                    }
                                    break;
                                default:
                                    $result = $funcName($params[0]);
                                     break;
                            }
                        }
                        break;
                    case 2:
                        if (!$this->RDP_onlyparse) {
                            switch($funcName) {
                                case 'atan2':
                                    if (is_numeric($params[0]) && is_numeric($params[1]))
                                    {
                                        $result = $funcName(floatval($params[0]),floatval($params[1]));
                                    }
                                    else
                                    {
                                        $result = NAN;
                                    }
                                    break;
                                case 'mktime':
                                    if (is_numeric($params[0]) && is_numeric($params[1]))
                                    {
                                        $result = $funcName(intval($params[0]),intval($params[1]));
                                    }
                                    else
                                    {
                                        $result = NAN;
                                    }
                                    break;
                                default:
                                    $result = $funcName($params[0], $params[1]);
                                     break;
                            }
                        }
                        break;
                    case 3:
                        if (!$this->RDP_onlyparse) {
                            $result = $funcName($params[0], $params[1], $params[2]);
                        }
                        break;
                    case 4:
                        if (!$this->RDP_onlyparse) {
                            $result = $funcName($params[0], $params[1], $params[2], $params[3]);
                        }
                        break;
                    case 5:
                        if (!$this->RDP_onlyparse) {
                            $result = $funcName($params[0], $params[1], $params[2], $params[3], $params[4]);
                        }
                        break;
                    case 6:
                        if (!$this->RDP_onlyparse) {
                            $result = $funcName($params[0], $params[1], $params[2], $params[3], $params[4], $params[5]);
                        }
                        break;
                    default:
                        $this->RDP_AddError(sprintf($this->gT("Unsupported number of arguments: %s", $argsPassed)), $funcNameToken);
                        return false;
                    }

                } else {
                    $this->RDP_AddError(sprintf($this->gT("Function does not support %s arguments. "), $argsPassed)
                            . sprintf($this->gT("Function supports this many arguments, where -1=unlimited: %s."), implode(',', $numArgsAllowed)), $funcNameToken);
                    return false;
                }
            }
            catch (Exception $e)
            {
                $this->RDP_AddError($e->getMessage(),$funcNameToken);
                return false;
            }
            $token = array($result,$funcNameToken[1],'NUMBER');
            $this->RDP_StackPush($token);
            return true;
        }
    }

    /**
     * Add user functions to array of allowable functions within the equation.
     * $functions is an array of key to value mappings like this:
     * See $this->RDP_ValidFunctions for examples of the syntax
     * @param array $functions
     */

    public function RegisterFunctions(array $functions) {
        $this->RDP_ValidFunctions= array_merge($this->RDP_ValidFunctions, $functions);
    }

    /**
     * Set the value of a registered variable
     * @param $op - the operator (=,*=,/=,+=,-=)
     * @param <type> $name
     * @param <type> $value
     */
    private function RDP_SetVariableValue($op,$name,$value)
    {
        if ($this->RDP_onlyparse)
        {
            return 1;
        }
        return LimeExpressionManager::SetVariableValue($op, $name, $value);
    }

    /**
     * Split a soure string into STRING vs. EXPRESSION, where the latter is surrounded by unescaped curly braces.
     * This verson properly handles nested curly braces and curly braces within strings within curly braces - both of which are needed to better support JavaScript
     * Users still need to add a space or carriage return after opening braces (and ideally before closing braces too) to avoid  having them treated as expressions.
     * @param <type> $src
     * @return string
     */
    public function asSplitStringOnExpressions($src)
    {
        $parts = preg_split($this->RDP_ExpressionRegex,$src,-1,(PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
        $count = count($parts);
        $tokens = array();
        $inSQString=false;
        $inDQString=false;
        $curlyDepth=0;
        $thistoken=array();
        $offset=0;
        for ($j=0;$j<$count;++$j)
        {
            switch($parts[$j])
            {
                case '{':
                    if ($j < ($count-1) && preg_match('/\s|\n|\r/',substr($parts[$j+1],0,1)))
                    {
                        // don't count this as an expression if the opening brace is followed by whitespace
                        $thistoken[] = '{';
                        $thistoken[] = $parts[++$j];
                    }
                    else if ($inDQString || $inSQString)
                    {
                        // just push the curly brace
                        $thistoken[] = '{';
                    }
                    else if ($curlyDepth>0)
                    {
                        // a nested curly brace - just push it
                        $thistoken[] = '{';
                        ++$curlyDepth;
                    }
                    else
                    {
                        // then starting an expression - save the out-of-expression string
                        if (count($thistoken) > 0)
                        {
                            $_token = implode('',$thistoken);
                            $tokens[] = array(
                                $_token,
                                $offset,
                                'STRING'
                                );
                            $offset += strlen($_token);
                        }
                        $curlyDepth=1;
                        $thistoken = array();
                        $thistoken[] = '{';
                    }
                    break;
                case '}':
                    // don't count this as an expression if the closing brace is preceded by whitespace
                    if ($j > 0 && preg_match('/\s|\n|\r/',substr($parts[$j-1],-1,1)))
                    {
                        $thistoken[] = '}';
                    }
                    else if ($curlyDepth==0)
                    {
                        // just push the token
                        $thistoken[] = '}';
                    }
                    else
                    {
                        if ($inSQString || $inDQString)
                        {
                            // just push the token
                            $thistoken[] = '}';
                        }
                        else
                        {
                            --$curlyDepth;
                            if ($curlyDepth==0)
                            {
                                // then closing expression
                                $thistoken[] = '}';
                                $_token = implode('',$thistoken);
                                $tokens[] = array(
                                    $_token,
                                    $offset,
                                    'EXPRESSION'
                                    );
                                $offset += strlen($_token);
                                $thistoken=array();
                            }
                            else
                            {
                                // just push the token
                                $thistoken[] = '}';
                            }
                        }
                    }
                    break;
                case '\'':
                    $thistoken[] = '\'';
                    if ($curlyDepth==0)
                    {
                        // only counts as part of a string if it is already within an expression
                    }
                    else
                    {
                        if ($inDQString)
                        {
                            // then just push the single quote
                        }
                        else
                        {
                            if ($inSQString) {
                                $inSQString=false;  // finishing a single-quoted string
                            }
                            else {
                                $inSQString=true;   // starting a single-quoted string
                            }
                        }
                    }
                    break;
                case '"':
                    $thistoken[] = '"';
                    if ($curlyDepth==0)
                    {
                        // only counts as part of a string if it is already within an expression
                    }
                    else
                    {
                        if ($inSQString)
                        {
                            // then just push the double quote
                        }
                        else
                        {
                            if ($inDQString) {
                                $inDQString=false;  // finishing a double-quoted string
                            }
                            else {
                                $inDQString=true;   // starting a double-quoted string
                            }
                        }
                    }
                    break;
                case '\\':
                    if ($j < ($count-1)) {
                        $thistoken[] = $parts[$j++];
                        $thistoken[] = $parts[$j];
                    }
                    break;
                default:
                    $thistoken[] = $parts[$j];
                    break;
            }
        }
        if (count($thistoken) > 0)
        {
            $tokens[] = array(
                implode('',$thistoken),
                $offset,
                'STRING',
            );
        }
        return $tokens;
    }

    /**
     * Specify the survey  mode for this survey.  Options are 'survey', 'group', and 'question'
     * @param type $mode
     */
    public function SetSurveyMode($mode)
    {
        if (preg_match('/^group|question|survey$/',$mode))
        {
            $this->surveyMode = $mode;
        }
    }

    /**
     * Pop a value token off of the stack
     * @return token
     */

    private function RDP_StackPop()
    {
        if (count($this->RDP_stack) > 0)
        {
            return array_pop($this->RDP_stack);
        }
        else
        {
            $this->RDP_AddError($this->gT("Tried to pop value off of empty stack"), NULL);
            return NULL;
        }
    }

    /**
     * Stack only holds values (number, string), not operators
     * @param array $token
     */

    private function RDP_StackPush(array $token)
    {
        if ($this->RDP_onlyparse)
        {
            // If only parsing, still want to validate syntax, so use "1" for all variables
            switch($token[2])
            {
                case 'DQ_STRING':
                case 'SQ_STRING':
                    $this->RDP_stack[] = array(1,$token[1],$token[2]);
                    break;
                case 'NUMBER':
                default:
                    $this->RDP_stack[] = array(1,$token[1],'NUMBER');
                    break;
            }
        }
        else
        {
            $this->RDP_stack[] = $token;
        }
    }

    /**
     * Split the source string into tokens, removing whitespace, and categorizing them by type.
     *
     * @param $src
     * @return array
     */

    private function RDP_Tokenize($src)
    {
        // $tokens0 = array of tokens from equation, showing value and offset position.  Will include SPACE, which should be removed
        $tokens0 = preg_split($this->RDP_TokenizerRegex,$src,-1,(PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE));

        // $tokens = array of tokens from equation, showing value, offsete position, and type.  Will not contain SPACE, but will contain OTHER
        $tokens = array();
        // Add token_type to $tokens:  For each token, test each categorization in order - first match will be the best.
        for ($j=0;$j<count($tokens0);++$j)
        {
            for ($i=0;$i<count($this->RDP_CategorizeTokensRegex);++$i)
            {
                $token = $tokens0[$j][0];
                if (preg_match($this->RDP_CategorizeTokensRegex[$i],$token))
                {
                    if ($this->RDP_TokenType[$i] !== 'SPACE') {
                        $tokens0[$j][2] = $this->RDP_TokenType[$i];
                        if ($this->RDP_TokenType[$i] == 'DQ_STRING' || $this->RDP_TokenType[$i] == 'SQ_STRING')
                        {
                            // remove outside quotes
                            $unquotedToken = str_replace(array('\"',"\'","\\\\"),array('"',"'",'\\'),substr($token,1,-1));
                            $tokens0[$j][0] = $unquotedToken;
                        }
                        $tokens[] = $tokens0[$j];   // get first matching non-SPACE token type and push onto $tokens array
                    }
                    break;  // only get first matching token type
                }
            }
        }
        return $tokens;
    }

    /**
     * Unit test the asSplitStringOnExpressions() function to ensure that accurately parses out all expressions
     * surrounded by curly braces, allowing for strings and escaped curly braces.
     */

    static function UnitTestStringSplitter()
    {
       $tests = <<<EOD
This string does not contain an expression
"This is only a string"
"this is a string that contains {something in curly brace}"
How about nested curly braces, like {INSERTANS:{SGQ}}?
This example has escaped curly braces like \{this is not an equation\}
Should the parser check for unmatched { opening curly braces?
What about for unmatched } closing curly braces?
What if there is a { space after the opening brace?}
What about a {space before the closing brace }?
What about an { expression nested {within a string} that has white space after the opening brace}?
This {expression has {a nested curly brace { plus ones with whitespace around them} - they should be properly captured} into an expression  with sub-expressions.
This {is a string {since it does not close } all of its curly} braces.
This uses \{escaped curly braces\} which should generate output showing curly braces without the escapes
This uses a double curly brace syntax, like that used for \{\{placeholders\}\} and Yii \{\{tables\}\}
Can {expressions contain 'single' or "double" quoted strings}?
Can an expression contain a perl regular expression like this {'/^\d{3}-\d{2}-\d{4}$/'}?
[img src="images/mine_{Q1}.png"/]
[img src="images/mine_" + {Q1} + ".png"/]
[img src={implode('','"images/mine_',Q1,'.png"')}/]
[img src="images/mine_{if(Q1=="Y",'yes','no')}.png"/]
[img src="images/mine_{if(Q1=="Y",'sq with {nested braces}',"dq with {nested braces}")}.png"/]
{name}, you said that you are {age} years old, and that you have {numKids} {if((numKids==1),'child','children')} and {numPets} {if((numPets==1),'pet','pets')} running around the house. So, you have {numKids + numPets} wild {if((numKids + numPets ==1),'beast','beasts')} to chase around every day.
Since you have more {if((INSERT61764X1X3 > INSERT61764X1X4),'children','pets')} than you do {if((INSERT61764X1X3 > INSERT61764X1X4),'pets','children')}, do you feel that the {if((INSERT61764X1X3 > INSERT61764X1X4),'pets','children')} are at a disadvantage?
Here is a String that failed to parse prior to fixing the preg_split() command to avoid recursive search of sub-strings: [{((617167X9X3241 == "Y" or 617167X9X3242 == "Y" or 617167X9X3243 == "Y" or 617167X9X3244 == "Y" or 617167X9X3245 == "Y" or 617167X9X3246 == "Y" or 617167X9X3247 == "Y" or 617167X9X3248 == "Y" or 617167X9X3249 == "Y") and (617167X9X3301 == "Y" or 617167X9X3302 == "Y" or 617167X9X3303 == "Y" or 617167X9X3304 == "Y" or 617167X9X3305 == "Y" or 617167X9X3306 == "Y" or 617167X9X3307 == "Y" or 617167X9X3308 == "Y" or 617167X9X3309 == "Y"))}] Here is the question.
EOD;

        $em = new ExpressionManager();

        foreach(explode("\n",$tests) as $test)
        {
            $tokens = $em->asSplitStringOnExpressions($test);
            print '<b>' . $test . '</b><hr/>';
            print '<code>';
            print implode("<br/>\n",explode("\n",print_r($tokens,TRUE)));
            print '</code><hr/>';
        }
    }

    /**
     * Unit test the Tokenizer - Tokenize and generate a HTML-compatible print-out of a comprehensive set of test cases
     */

    static function UnitTestTokenizer()
    {
        // Comprehensive test cases for tokenizing
        $tests = <<<EOD
        String:  "what about regular expressions, like for SSN (^\d{3}-\d{2}-\d{4}) or US phone# ((?:\(\d{3}\)\s*\d{3}-\d{4})"
        String:  "Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"
        String:  "can single quoted strings" . 'contain nested \'quoted sections\'?';
        Parens:  upcase('hello');
        Numbers:  42 72.35 -15 +37 42A .5 0.7
        And_Or: (this and that or the other);  Sandles, sorting; (a && b || c)
        Words:  hi there, my name is C3PO!
        UnaryOps: ++a, --b !b
        BinaryOps:  (a + b * c / d)
        Comparators:  > >= < <= == != gt ge lt le eq ne (target large gents built agile less equal)
        Assign:  = += -= *= /=
        SGQA:  1X6X12 1X6X12ber1 1X6X12ber1_lab1 3583X84X249 12X3X5lab1_ber#1 1X6X12.NAOK 1X6X12ber1.NAOK 1X6X12ber1_lab1.NAOK 3583X84X249.NAOK 12X3X5lab1_ber#1.NAOK
        Errors: Apt # 10C; (2 > 0) ? 'hi' : 'there'; array[30]; >>> <<< /* this is not a comment */ // neither is this
        Words:  q5pointChoice q5pointChoice.bogus q5pointChoice.code q5pointChoice.mandatory q5pointChoice.NAOK q5pointChoice.qid q5pointChoice.question q5pointChoice.relevance q5pointChoice.shown q5pointChoice.type
EOD;

        $em = new ExpressionManager();

        $atests = explode("\n",$tests);
        array_push($atests,'"hi\nthere\nhow\nare\nyou?\n"');

        foreach($atests as $test)
        {
            $tokens = $em->RDP_Tokenize($test);
            print '<b>' . $test . '</b><hr/>';
            print '<code>';
            print implode("<br/>\n",explode("\n",print_r($tokens,TRUE)));
            print '</code><hr/>';
        }
    }

    /**
     * Show a table of allowable Expression Manager functions
     * @return string
     */

    static function ShowAllowableFunctions()
    {
        $em = new ExpressionManager();
        $output = "<h3>Functions Available within Expression Manager</h3>\n";
        $output .= "<table border='1'><tr><th>Function</th><th>Meaning</th><th>Syntax</th><th>Reference</th></tr>\n";
        foreach ($em->RDP_ValidFunctions as $name => $func) {
            $output .= "<tr><td>" . $name . "</td><td>" . $func[2] . "</td><td>" . $func[3] . "</td><td><a href='" . $func[4] . "'>" . $func[4] . "</a>&nbsp;</td></tr>\n";
        }
        $output .= "</table>\n";
        return $output;
    }

    /**
     * Unit test the Evaluator, allowing for passing in of extra functions, variables, and tests
     */

    static function UnitTestEvaluator()
    {
        // Some test cases for Evaluator
        $vars = array(
'one' => array('sgqa'=>'one', 'code'=>1, 'jsName'=>'java_one', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>4),
'two' => array('sgqa'=>'two', 'code'=>2, 'jsName'=>'java_two', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>4),
'three' => array('sgqa'=>'three', 'code'=>3, 'jsName'=>'java_three', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>4),
'four' => array('sgqa'=>'four', 'code'=>4, 'jsName'=>'java_four', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>1),
'five' => array('sgqa'=>'five', 'code'=>5, 'jsName'=>'java_five', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>1),
'six' => array('sgqa'=>'six', 'code'=>6, 'jsName'=>'java_six', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>1),
'seven' => array('sgqa'=>'seven', 'code'=>7, 'jsName'=>'java_seven', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>5),
'eight' => array('sgqa'=>'eight', 'code'=>8, 'jsName'=>'java_eight', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>5),
'nine' => array('sgqa'=>'nine', 'code'=>9, 'jsName'=>'java_nine', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>5),
'ten' => array('sgqa'=>'ten', 'code'=>10, 'jsName'=>'java_ten', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
'half' => array('sgqa'=>'half', 'code'=>.5, 'jsName'=>'java_half', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
'hi' => array('sgqa'=>'hi', 'code'=>'there', 'jsName'=>'java_hi', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
'hello' => array('sgqa'=>'hello', 'code'=>"Tom", 'jsName'=>'java_hello', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
'a' => array('sgqa'=>'a', 'code'=>0, 'jsName'=>'java_a', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>2),
'b' => array('sgqa'=>'b', 'code'=>0, 'jsName'=>'java_b', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>2),
'c' => array('sgqa'=>'c', 'code'=>0, 'jsName'=>'java_c', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>2),
'd' => array('sgqa'=>'d', 'code'=>0, 'jsName'=>'java_d', 'readWrite'=>'Y', 'gseq'=>2,'qseq'=>2),
'eleven' => array('sgqa'=>'eleven', 'code'=>11, 'jsName'=>'java_eleven', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
'twelve' => array('sgqa'=>'twelve', 'code'=>12, 'jsName'=>'java_twelve', 'readWrite'=>'Y', 'gseq'=>1,'qseq'=>1),
// Constants
'ASSESSMENT_HEADING' => array('sgqa'=>'ASSESSMENT_HEADING', 'code'=>'"Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"', 'jsName'=>'', 'readWrite'=>'N'),
'QID' => array('sgqa'=>'QID', 'code'=>'value for {QID}', 'jsName'=>'', 'readWrite'=>'N'),
'QUESTIONHELP' => array('sgqa'=>'QUESTIONHELP', 'code'=>'"can single quoted strings" . \'contain nested \'quoted sections\'?', 'jsName'=>'', 'readWrite'=>'N'),
'QUESTION_HELP' => array('sgqa'=>'QUESTION_HELP', 'code'=>'Can strings have embedded <tags> like <html>, or even unbalanced "quotes or entities without terminal semicolons like &amp and  &lt?', 'jsName'=>'', 'readWrite'=>'N'),
'NUMBEROFQUESTIONS' => array('sgqa'=>'NUMBEROFQUESTIONS', 'code'=>'value for {NUMBEROFQUESTIONS}', 'jsName'=>'', 'readWrite'=>'N'),
'THEREAREXQUESTIONS' => array('sgqa'=>'THEREAREXQUESTIONS', 'code'=>'value for {THEREAREXQUESTIONS}', 'jsName'=>'', 'readWrite'=>'N'),
'TOKEN:FIRSTNAME' => array('sgqa'=>'TOKEN:FIRSTNAME', 'code' => 'value for {TOKEN:FIRSTNAME}', 'jsName' => '', 'readWrite' => 'N'),
'WELCOME' => array('sgqa'=>'WELCOME', 'code'=>'value for {WELCOME}', 'jsName'=>'', 'readWrite'=>'N'),
// also include SGQA values and read-only variable attributes
'12X34X56' => array('sgqa'=>'12X34X56', 'code'=>5, 'jsName'=>'', 'readWrite'=>'N', 'gseq'=>1,'qseq'=>1),
'12X3X5lab1_ber' => array('sgqa'=>'12X3X5lab1_ber', 'code'=>10, 'jsName'=>'', 'readWrite'=>'N', 'gseq'=>1,'qseq'=>1),
'q5pointChoice' => array('sgqa'=>'q5pointChoice', 'code'=>3, 'jsName'=>'java_q5pointChoice', 'readWrite'=>'N','shown'=>'Father', 'relevance'=>1, 'type'=>'5', 'question'=>'(question for q5pointChoice)', 'qid'=>14,'gseq'=>2,'qseq'=>14),
'qArrayNumbers_ls1_min' => array('sgqa'=>'qArrayNumbers_ls1_min', 'code'=> 7, 'jsName'=>'java_qArrayNumbers_ls1_min', 'readWrite'=>'N','shown'=> 'I love LimeSurvey', 'relevance'=>1, 'type'=>'A', 'question'=>'(question for qArrayNumbers)', 'qid'=>6,'gseq'=>2,'qseq'=>6),
'12X3X5lab1_ber#1' => array('sgqa'=>'12X3X5lab1_ber#1', 'code'=> 15, 'jsName'=>'', 'readWrite'=>'N', 'gseq'=>1,'qseq'=>1),
'zero' => array('sgqa'=>'zero', 'code'=>0, 'jsName'=>'java_zero', 'gseq'=>0,'qseq'=>0),
'empty' => array('sgqa'=>'empty', 'code'=>'', 'jsName'=>'java_empty', 'gseq'=>0,'qseq'=>0),
'BREAKS' => array('sgqa'=>'BREAKS', 'code'=>"1\n2\n3", 'jsName'=>'', 'readWrite'=>'N'),
        );

        // Syntax for $tests is
        // expectedResult~expression
        // if the expected result is an error, use NULL for the expected result
        $tests  = <<<EOD
<B>Empty Vs. Empty</B>~"<B>Empty Vs. Empty</B>"
1~'' == ''
0~'' != ''
0~'' > ''
1~'' >= ''
0~'' < ''
1~'' <= ''
1~!''
~('' and '')
~('' or '')
<B>Empty Vs. Zero</B>~"<B>Empty Vs. Zero</B>"
0~'' == 0
1~'' != 0
0~'' > 0
0~'' >= 0
0~'' < 0
0~'' <= 0
1~!''
1~!0
0~('' and 0)
0~('' or 0)
<B>Empty Vs. Constant</B>~"<B>Empty Vs. Constant</B>"
0~'' == 3
1~'' != 3
0~'' > 3
0~'' >= 3
0~'' < 3
0~'' <= 3
1~!''
0~!3
0~('' and 3)
1~('' or 3)
<B>Empty Vs. Empty_Var</B>~"<B>Empty Vs. Empty_Var</B>"
1~'' == empty
0~'' != empty
0~'' > empty
1~'' >= empty
0~'' < empty
1~'' <= empty
1~!''
1~!empty
~('' and empty)
~('' or empty)
<B>Empty_Var Vs. Zero</B>~"<B>Empty_Var Vs. Zero</B>"
0~empty == 0
1~empty != 0
0~empty > 0
0~empty >= 0
0~empty < 0
0~empty <= 0
1~!empty
1~!0
0~(empty and 0)
0~(empty or 0)
<B>Empty_Var Vs. Zero</B>~"<B>Empty_Var Vs. Zero</B>"
0~empty == zero
1~empty != zero
0~empty > zero
0~empty >= zero
0~empty < zero
0~empty <= zero
1~!empty
1~!zero
0~(empty and zero)
0~(empty or zero)
<B>Empty_Var Vs. Constant</B>~"<B>Empty_Var Vs. Constant</B>"
0~empty == 3
1~empty != 3
0~empty > 3
0~empty >= 3
0~empty < 3
0~empty <= 3
1~!empty
0~!3
0~(empty and 3)
1~(empty or 3)
<B>Solution: Empty_Var Vs. Zero</B>~"<B>Solution: Empty_Var Vs. Zero</B>"
0~!is_empty(empty) && (empty == 0)
0~!is_empty(five) && (five == 0)
1~!is_empty(zero) && (zero == 0)
0~!is_empty(empty) && (empty > 0)
0~!is_empty(empty) && (empty >= 0)
0~!is_empty(empty) && (empty < 0)
0~!is_empty(empty) && (empty <= 0)
0~!is_empty(empty) && ((empty and 0))
0~!is_empty(empty) && ((empty or 0))
<B>Solution: Empty_Var Vs. Zero</B>~"<B>Solution: Empty_Var Vs. Zero</B>"
0~!is_empty(empty) && (empty == zero)
0~!is_empty(five) && (five == zero)
1~!is_empty(zero) && (zero == zero)
0~!is_empty(empty) && (empty > zero)
0~!is_empty(empty) && (empty >= zero)
0~!is_empty(empty) && (empty < zero)
0~!is_empty(empty) && (empty <= zero)
0~!is_empty(empty) && ((empty and zero))
0~!is_empty(empty) && ((empty or zero))
<B>Solution: Empty_Var Vs. Constant</B>~"<B>Solution: Empty_Var Vs. Constant</B>"
0~!is_empty(empty) && (empty < 3)
0~!is_empty(empty) && (empty <= 3)
<B>Solution: Empty_Var Vs. Variable</B>~"<B>Solution: Empty_Var Vs. Variable</B>"
0~!is_empty(empty) && (empty < five)
0~!is_empty(empty) && (empty <= five)
<B>Solution: The Hard One is Empty_Var != 0</B>~"<B>Solution: The Hard One is Empty_Var != 0</B>"
1~(empty != 0)
1~!is_empty(empty) && (empty != 0)
1~is_empty(empty) || (empty != 0)
1~is_empty(empty) || (empty != zero)
0~is_empty(zero) || (zero != 0)
1~is_empty(five) || (five != 0)
<b>SETUP</b>~'<b>SETUP</b>'
&quot;Can strings contain embedded \&quot;quoted passages\&quot; (and parentheses + other characters?)?&quot;~a=htmlspecialchars(ASSESSMENT_HEADING)
&quot;can single quoted strings&quot; . &#039;contain nested &#039;quoted sections&#039;?~b=htmlspecialchars(QUESTIONHELP)
Can strings have embedded &lt;tags&gt; like &lt;html&gt;, or even unbalanced &quot;quotes or entities without terminal semicolons like &amp;amp and  &amp;lt?~c=htmlspecialchars(QUESTION_HELP)
<span id="d" style="border-style: solid; border-width: 2px; border-color: green">Hi there!</span>~d='<span id="d" style="border-style: solid; border-width: 2px; border-color: green">Hi there!</span>'
<b>FUNCTIONS</b>~'<b>FUNCTIONS</b>'
5~abs(five)
5~abs(-five)
0.2~acos(cos(0.2))
0~acos(cos(pi()))-pi()
&quot;Can strings contain embedded \\&quot;quoted passages\\&quot; (and parentheses + other characters?)?&quot;~addslashes(a)
&quot;can single quoted strings&quot; . &#039;contain nested &#039;quoted sections&#039;?~addslashes(b)
Can strings have embedded &lt;tags&gt; like &lt;html&gt;, or even unbalanced &quot;quotes or entities without terminal semicolons like &amp;amp and  &amp;lt?~addslashes(c)
0.2~asin(sin(0.2))
0.2~atan(tan(0.2))
0~atan2(0,1)
1~ceil(0.3)
1~ceil(0.7)
0~ceil(-0.3)
0~ceil(-0.7)
10~ceil(9.1)
1~checkdate(1,29,1967)
0~checkdate(2,29,1967)
0.2~cos(acos(0.2))
5~count(1,2,3,4,5)
0~count()
5~count(one,two,three,four,five)
2~count(a,'',c)
NULL~date('F j, Y, g:i a',time())
April 5, 2006, 1:02 am~date('F j, Y, g:i a',mktime(1,2,3,4,5,6))
20~floor(exp(3))
0~floor(asin(sin(pi())))
9~floor(9.9)
3~floor(pi())
January 12, 2012, 5:27 pm~date('F j, Y, g:i a',1326410867)
January 12, 2012, 11:27 pm~gmdate('F j, Y, g:i a',1326410867)
"Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"~html_entity_decode(a)
"can single quoted strings" . &#039;contain nested &#039;quoted sections&#039;?~html_entity_decode(b)
Can strings have embedded <tags> like <html>, or even unbalanced "quotes or entities without terminal semicolons like &amp and  &lt?~html_entity_decode(c)
&quot;Can strings contain embedded \&quot;quoted passages\&quot; (and parentheses + other characters?)?&quot;~htmlentities(a)
&quot;can single quoted strings&quot; . &#039;contain nested &#039;quoted sections&#039;?~htmlentities(b)
Can strings have embedded &lt;tags&gt; like &lt;html&gt;, or even unbalanced &quot;quotes or entities without terminal semicolons like &amp;amp and &amp;lt?~htmlentities(c)
1~c==htmlspecialchars(htmlspecialchars_decode(c))
1~b==htmlspecialchars(htmlspecialchars_decode(b))
1~a==htmlspecialchars(htmlspecialchars_decode(a))
"Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"~htmlspecialchars_decode(a)
"can single quoted strings" . 'contain nested 'quoted sections'?~htmlspecialchars_decode(b)
Can strings have embedded like , or even unbalanced "quotes or entities without terminal semicolons like & and <?~htmlspecialchars_decode(c)
"Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"~htmlspecialchars(a)
"can single quoted strings" . 'contain nested 'quoted sections'?~htmlspecialchars(b)
Can strings have embedded <tags> like <html>, or even unbalanced "quotes or entities without terminal semicolons like &amp and &lt?~htmlspecialchars(c)
9~idate('B',1326410867)
0~if('0',1,0)
0~if(0,1,0)
1~if(!0,1,0)
0~if(!(!0),1,0)
1~if('true',1,0)
1~if('false',1,0)
1~if('00',1,0)
0~if('',1,0)
1~if('A',1,0)
0~if(empty,1,0)
4~if(5 > 7,2,4)
1~if(' ',1,0)
there~if((one > two),'hi','there')
64~if((one < two),pow(2,6),pow(6,2))
H e l l o~implode(' ','H','e','l','l','o')
1|2|3|4|5~implode('|',one,two,three,four,five)
4~intval('4')
4~intval('100',2)
5~intval(5.7)
0~is_empty(four)
1~is_empty(empty)
1~is_empty('')
0~is_empty(0)
0~is_empty('0')
0~is_empty('false')
0~is_empty('NULL')
0~is_empty(1)
1~is_empty(one==two)
0~!is_empty(one==two)
1~is_float(half)
0~is_float(one)
1~is_float(pi())
0~is_float(5)
0~is_int(half)
1~is_int(one)
0~is_nan(half)
1~is_nan(WELCOME)
1~is_null(sdfjskdfj)
0~is_null(four)
0~is_numeric(empty)
1~is_numeric('1')
1~is_numeric(four)
0~is_numeric('hi')
1~is_numeric(five)
0~is_numeric(hi)
0~is_string(four)
1~is_string('hi')
1~is_string(hi)
1, 2, 3, 4, 5~list(one,two,three,min(four,five,six),max(three,four,five))
11, 12~list(eleven,twelve)
0, 1, 3, 5~list(0,one,'',three,'',five)
1~log(exp(1))
2~log(exp(2))
I was trimmed   ~ltrim('     I was trimmed   ')
10~max(5,6,10,-20)
6~max(five,(one + (two * four)- three))
6~max((one + (two * four)- three))
212~5 + max(1,(2+3),(4 + (5 + 6)),((7 + 8) + 9),((10 + 11), 12),(13 + (14 * 15) - 16))
29~five + max(one, (two + three), (four + (five + six)),((seven + eight) + nine),((ten + eleven), twelve),(one + (two * three) - four))
1024~max(one,(two*three),pow(four,five),six)
2~max(one,two)
5~max(one,two,three,four,five)
-5~min(-5,10,15,12,-3)
1~min(five,four,one,two,three)
1344765967~mktime(5,6,7,8)
1144191723~mktime(1,2,3,4,5,6)
1,000~number_format(1000)
1,000.23~number_format(1000.23)
1,234,567~number_format(1234567)
315~ceil(100*pi())
1~pi() == pi() * 2 - pi()
4~pow(2,2)
27~pow(3,3)
=~quoted_printable_decode(quoted_printable_encode('='))
\\$~quotemeta('$')
IGNORE THIS ERROR~rand(3,5)
0~(a=rand())-a
1~regexMatch('/embedded/',c)
1~regexMatch('/^.*embedded.*$/',c)
0~regexMatch('/joe/',c)
1~regexMatch('/(?:dog|cat)food/','catfood stinks')
1~regexMatch('/(?:dog|cat)food/','catfood stinks')
1~regexMatch('/[0-9]{3}-[0-9]{2}-[0-9]{4}/','123-45-6789')
1~regexMatch('/\d{3}-\d{2}-\d{4}/','123-45-6789')
1~regexMatch('/(?:\(\d{3}\))\s*\d{3}-\d{4}/','(212) 555-1212')
0~round(0.2)
1~round(.8)
0.07~0.01 + 0.06
0.07~round(0.01 + 0.06,10)
     I was trimmed~rtrim('     I was trimmed   ')
0.2~sin(asin(0.2))
1~sin(pi()/2)
1~sin(pi()/2) == sin(.5 * pi())
1~sin(0.5 * pi())
hello,5~sprintf('%s,%d','hello',5)
2~sqrt(4)
158~round(stddev(4,5,6,7,8)*100)
hello-----~str_pad('hello',10,'-')
hello     ~str_pad('hello',10)
hello~str_pad('hello',3)
testtesttest~str_repeat('test',3)
I am awesome~str_replace('You are','I am','You are awesome')
I love LimeSurvey~str_replace('like','love','I like LimeSurvey')
1~0==strcasecmp('Hello','hello')
0~0==strcasecmp('Hello','hi')
1~0==strcmp('Hello','Hello')
0~0==strcmp('Hello','hi')
Hi there!~c=strip_tags(d)
hello~strip_tags('<b>hello</b>')
5~stripos('ABCDEFGHI','f')
hi~stripslashes('\\h\\i')
FGHI~stristr('ABCDEFGHI','fg')
5~strlen('12345')
5~strlen(hi)
0~strpos('ABCDEFGHI','f')
5~strpos('ABCDEFGHI','F')
2~strpos('I like LimeSurvey','like')
54321~strrev('12345')
0~strstr('ABCDEFGHI','fg')
FGHI~strstr('ABCDEFGHI','FG')
hi there!~strtolower(c)
HI THERE!~strtoupper(c)
678~substr('1234567890',5,3)
15~sum(1,2,3,4,5)
15~sum(one,two,three,four,five)
0.2~tan(atan(0.2))
IGNORE THIS ERROR~time()
I was trimmed~trim('     I was trimmed   ')
Hi There You~ucwords('hi there you')
<b>EXPRESSIONS</b>~'<b>EXPRESSIONS</b>'
1~!'0'
1~0 eq '0'
0~0 ne '0'
0~0 eq empty
1~0 ne empty
0~0 eq ''
1~0 ne ''
0~'' < 10
0~0 < empty
1~0 <= empty
0~0 > empty
1~0 >= empty
0~'0' eq empty
1~'0' ne empty
0~'0' < empty
1~'0' <= empty
0~'0' > empty
1~'0' >= empty
1~empty eq empty
0~empty ne empty
0~'' > 0
0~' ' > 0
1~!0
0~!' '
0~!'A'
0~!1
0~!'1'
1~!''
1~!empty
1~'0'==0
0~'A'>0
0~'A'<0
0~'A'==0
0~'A'>=0
0~'A'<=0
0~0>'A'
0~0>='B'
0~0=='C'
0~0<'D'
0~0<='E'
1~0!='F'
1~'A' or 'B'
1~'A' and 'B'
0~'A' eq 'B'
1~'A' ne 'B'
1~'A' < 'B'
1~'A' <= 'B'
0~'A' > 'B'
0~'A' >= 'B'
AB~'A' + 'B'
NAN~'A' - 'B'
NAN~'A' * 'B'
NAN~'A' / 'B'
1~'A' or empty
0~'A' and empty
0~'A' eq empty
1~'A' ne empty
0~'A' < empty
0~'A' <= empty
1~'A' > empty
1~'A' >= empty
A~'A' + empty
NAN~'A' - empty
NAN~'A' * empty
NAN~'A' / empty
0~0 or empty
0~0 and empty
0~0 + empty
0~0 - empty
0~0 * empty
NAN~0 / empty
0~(-1 > 0)
0~zero
~empty
1~five > zero
1~five > empty
1~empty < 16
1~zero == empty
3~q5pointChoice.code
5~q5pointChoice.type
(question for q5pointChoice)~q5pointChoice.question
1~q5pointChoice.relevance
4~q5pointChoice.NAOK + 1
NULL~q5pointChoice.bogus
14~q5pointChoice.qid
7~qArrayNumbers_ls1_min.code
1~(one * (two + (three - four) + five) / six)
2.4~(one  * two) + (three * four) / (five * six)
50~12X34X56 * 12X3X5lab1_ber
1~c == 'Hi there!'
1~c == "Hi there!"
3~a=three
3~c=a
12~c*=four
15~c+=a
5~c/=a
-1~c-=six
24~one * two * three * four
-4~five - four - three - two
0~two * three - two - two - two
4~two * three - two
105~5 + 1, 7 * 15
7~7
15~10 + 5
24~12 * 2
10~13 - 3
3.5~14 / 4
5~3 + 1 * 2
1~one
there~hi
6.25~one * two - three / four + five
1~one + hi
1~two > one
1~two gt one
1~three >= two
1~three ge  two
0~four < three
0~four lt three
0~four <= three
0~four le three
0~four == three
0~four eq three
1~four != three
0~four ne four
NAN~one * hi
0~a='hello',b='',c=0
hello~a
0~c
0~one && 0
0~two and 0
1~five && 6
1~seven && eight
1~one or 0
1~one || 0
1~(one and 0) || (two and three)
value for {QID}~QID
"Can strings contain embedded \"quoted passages\" (and parentheses + other characters?)?"~ASSESSMENT_HEADING
"can single quoted strings" . 'contain nested 'quoted sections'?~QUESTIONHELP
Can strings have embedded <tags> like <html>, or even unbalanced "quotes or entities without terminal semicolons like &amp and  &lt?~QUESTION_HELP
value for {TOKEN:FIRSTNAME}~TOKEN:FIRSTNAME
value for {THEREAREXQUESTIONS}~THEREAREXQUESTIONS
15~12X3X5lab1_ber#1
1~three == three
1~three == 3
11~eleven
144~twelve * twelve
0~!three
8~five + + three
2~five + - three
<b>SYNTAX ERRORS</b>~'<b>SYNTAX ERRORS</b>'
NULL~*
NULL~three +
NULL~four * / seven
NULL~(five - three
NULL~five + three)
NULL~seven + = four
NULL~>
NULL~five > > three
NULL~seven > = four
NULL~seven >=
NULL~three &&
NULL~three ||
NULL~three +
NULL~three >=
NULL~three +=
NULL~three !
NULL~three *
NULL~five ! three
NULL~(5 + 7) = 8
NULL~&& four
NULL~min(
NULL~max three, four, five)
NULL~three four
NULL~max(three,four,five) six
NULL~WELCOME='Good morning'
NULL~TOKEN:FIRSTNAME='Tom'
NULL~NUMBEROFQUESTIONS+=3
NULL~NUMBEROFQUESTIONS*=4
NULL~NUMBEROFQUESTIONS/=5
NULL~NUMBEROFQUESTIONS-=6
NULL~'Tom'='tired'
NULL~max()
EOD;

        $atests = explode("\n",$tests);
        $atests[] = "1\n2\n3~BREAKS";
        $atests[] = "1<br />\n2<br />\n3~nl2br(BREAKS)";
        $atests[] = "hi<br />\nthere<br />\nhow<br />\nare<br />\nyou?~nl2br('hi\\nthere\\nhow\\nare\\nyou?')";
        $atests[] = "hi<br />\nthere,<br />\nuser!~nl2br(implode('\\n','hi','there,','user!'))";

        $LEM =& LimeExpressionManager::singleton();
        $em = new ExpressionManager();
        $LEM->setTempVars($vars);

        // manually set relevance status
        $_SESSION['relevanceStatus'] = array();
        foreach ($vars as $var) {
            if (isset($var['qseq'])) {
                $_SESSION['relevanceStatus'][$var['qseq']] = 1;
            }
        }

        $allJsVarnamesUsed = array();
        $body = '';
        $body .= '<table border="1"><tr><th>Expression</th><th>PHP Result</th><th>Expected</th><th>JavaScript Result</th><th>VarNames</th><th>JavaScript Eqn</th></tr>';
        $i=0;
        $javaScript = array();
        foreach($atests as $test)
        {
            ++$i;
            $values = explode("~",$test);
            $expectedResult = array_shift($values);
            $expr = implode("~",$values);
            $resultStatus = 'ok';
            $em->groupSeq=2;
            $em->questionSeq=3;
            $status = $em->RDP_Evaluate($expr);
            if ($status)
            {
                $allJsVarnamesUsed = array_merge($allJsVarnamesUsed,$em->GetJsVarsUsed());
            }
            $result = $em->GetResult();
            $valToShow = $result;   // htmlspecialchars($result,ENT_QUOTES,'UTF-8',false);
            $expectedToShow = $expectedResult; // htmlspecialchars($expectedResult,ENT_QUOTES,'UTF-8',false);
            $body .= "<tr>";
            $body .= "<td>" . $em->GetPrettyPrintString() . "</td>\n";
            if (is_null($result)) {
                $valToShow = "NULL";
            }
            if ($valToShow != $expectedToShow)
            {
                $resultStatus = 'error';
            }
            $body .= "<td class='" . $resultStatus . "'>" . $valToShow . "</td>\n";
            $body .= '<td>' . $expectedToShow . "</td>\n";
            $javaScript[] = $em->GetJavascriptTestforExpression($expectedToShow, $i);
            $body .= "<td id='test_" . $i . "'>&nbsp;</td>\n";
            $varsUsed = $em->GetVarsUsed();
            if (is_array($varsUsed) and count($varsUsed) > 0) {
                $varDesc = array();
                foreach ($varsUsed as $v) {
                    $varDesc[] = $v;
                }
                $body .= '<td>' . implode(',<br/>', $varDesc) . "</td>\n";
            }
            else {
                $body .= "<td>&nbsp;</td>\n";
            }
            $jsEqn = $em->GetJavaScriptEquivalentOfExpression();
            if ($jsEqn == '')
            {
                $body .= "<td>&nbsp;</td>\n";
            }
            else
            {
                $body .= '<td>' . $jsEqn . "</td>\n";
            }
            $body .= '</tr>';
        }
        $body .= '</table>';
        $body .= "<script type='text/javascript'>\n";
        $body .= "<!--\n";
        $body .= "var LEMgseq=2;\n";
        $body .= "var LEMmode='group';\n";
        $body .= "function recompute() {\n";
        $body .= implode("\n",$javaScript);
        $body .= "}\n//-->\n</script>\n";

        $allJsVarnamesUsed = array_unique($allJsVarnamesUsed);
        asort($allJsVarnamesUsed);
        $pre = '';
        $pre .= "<h3>Change some Relevance values to 0 to see how it affects computations</h3>\n";
        $pre .= '<table border="1"><tr><th>#</th><th>JsVarname</th><th>Starting Value</th><th>Relevance</th></tr>';
        $i=0;
        $LEMvarNameAttr=array();
        $LEMalias2varName=array();
        foreach ($allJsVarnamesUsed as $jsVarName)
        {
            ++$i;
            $pre .= "<tr><td>" .  $i . "</td><td>" . $jsVarName;
            foreach($vars as $k => $v) {
                if ($v['jsName'] == $jsVarName)
                {
                    $value = $v['code'];
                }
            }
            $pre .= "</td><td>" . $value . "</td><td><input type='text' id='relevance" . $i . "' value='1' onchange='recompute()'/>\n";
            $pre .= "<input type='hidden' id='" . $jsVarName . "' name='" . $jsVarName . "' value='" . $value . "'/>\n";
            $pre .= "</td></tr>\n";
            $LEMalias2varName[] = "'" . substr($jsVarName,5) . "':'" . $jsVarName . "'";
            $LEMalias2varName[] = "'" . $jsVarName . "':'" . $jsVarName . "'";
            $attrInfo = "'" . $jsVarName .  "': {'jsName':'" . $jsVarName . "'";

            $varInfo = $vars[substr($jsVarName,5)];
            foreach ($varInfo as $k=>$v) {
                if ($k == 'code') {
                    continue;   // will access it from hidden node
                }
               if ($k == 'shown') {
                    $k = 'shown';
                    $v = htmlspecialchars(preg_replace("/[[:space:]]/",' ',$v),ENT_QUOTES);
                }
                if ($k == 'jsName') {
                    continue;   // since already set
                }
                $attrInfo .= ", '" . $k . "':'" . $v . "'";

            }
            $attrInfo .= ",'qid':" . $i . "}";
            $LEMvarNameAttr[] = $attrInfo;
        }
        $pre .= "</table>\n";

        $pre .= "<script type='text/javascript'>\n";
        $pre .= "<!--\n";
        $pre .= "var LEMalias2varName= {". implode(",\n", $LEMalias2varName) ."};\n";
        $pre .= "var LEMvarNameAttr= {" . implode(",\n", $LEMvarNameAttr) . "};\n";
        $pre .= "var LEMradix = '.';\n";
        $pre .= "//-->\n</script>\n";

        print $pre;
        print $body;
    }

    /**
     * Stub to access LimeSurvey's functions for internationalizing strings
     * @param <type> $string
     * @return <type>
     */
    function gT($string)
    {
        // ultimately should call i8n functiouns
        global $clang;
        if (isset($clang)) {
            return $clang->gT($string);
        }
        else {
            return $string;
        }
    }
}

/**
 * Used by usort() to order Error tokens by their position within the string
 * This must be outside of the class in order to work in PHP 5.2
 * @param <type> $a
 * @param <type> $b
 * @return <type>
 */
function cmpErrorTokens($a, $b)
{
    if (is_null($a[1])) {
        if (is_null($b[1])) {
            return 0;
        }
        return 1;
    }
    if (is_null($b[1])) {
        return -1;
    }
    if ($a[1][1] == $b[1][1]) {
        return 0;
    }
    return ($a[1][1] < $b[1][1]) ? -1 : 1;
}

/**
 * Count the number of answered questions (non-empty)
 * @param <type> $args
 * @return int
 */
function exprmgr_count($args)
{
    $j=0;    // keep track of how many non-null values seen
    foreach ($args as $arg)
    {
        if ($arg != '') {
            ++$j;
        }
    }
    return $j;
}

/**
 * Count the number of answered questions (non-empty) which match the first argument
 * @param <type> $args
 * @return int
 */
function exprmgr_countif($args)
{
    $j=0;    // keep track of how many non-null values seen
    $match = array_shift($args);
    foreach ($args as $arg)
    {
        if ($arg == $match) {
            ++$j;
        }
    }
    return $j;
}

/**
 * Count the number of answered questions (non-empty) which meet the criteria (arg op value)
 * @param <type> $args
 * @return int
 */
function exprmgr_countifop($args)
{
    $j=0;
    $op = array_shift($args);
    $value = array_shift($args);
    foreach ($args as $arg)
    {
        switch($op)
        {
            case '==':  case 'eq': if ($arg == $value) { ++$j; } break;
            case '>=':  case 'ge': if ($arg >= $value) { ++$j; } break;
            case '>':   case 'gt': if ($arg > $value) { ++$j; } break;
            case '<=':  case 'le': if ($arg <= $value) { ++$j; } break;
            case '<':   case 'lt': if ($arg < $value) { ++$j; } break;
            case '!=':  case 'ne': if ($arg != $value) { ++$j; } break;
            case 'RX':
                try {
                    if (@preg_match($value, $arg))
                    {
                        ++$j;
                    }
                }
                catch (Exception $e) { }
                break;
        }
    }
    return $j;
}

/**
 * Sum of values of answered questions which meet the criteria (arg op value)
 * @param <type> $args
 * @return int
 */
function exprmgr_sumifop($args)
{
    $result=0;
    $op = array_shift($args);
    $value = array_shift($args);
    foreach ($args as $arg)
    {
        switch($op)
        {
            case '==':  case 'eq': if ($arg == $value) { $result += $arg; } break;
            case '>=':  case 'ge': if ($arg >= $value) { $result += $arg; } break;
            case '>':   case 'gt': if ($arg > $value) { $result += $arg; } break;
            case '<=':  case 'le': if ($arg <= $value) { $result += $arg; } break;
            case '<':   case 'lt': if ($arg < $value) { $result += $arg; } break;
            case '!=':  case 'ne': if ($arg != $value) { $result += $arg; } break;
            case 'RX':
                try {
                    if (@preg_match($value, $arg))
                    {
                        $result += $arg;
                    }
                }
                catch (Exception $e) { }
                break;
        }
    }
    return $result;
}

/**
 * If $test is true, return $ok, else return $error
 * @param <type> $test
 * @param <type> $ok
 * @param <type> $error
 * @return <type>
 */
function exprmgr_if($test,$ok,$error)
{
    if ($test)
    {
        return $ok;
    }
    else
    {
        return $error;
    }
}

/**
 * Join together $args[0-N] with ', '
 * @param <type> $args
 * @return <type>
 */
function exprmgr_list($args)
{
    $result="";
    $j=1;    // keep track of how many non-null values seen
    foreach ($args as $arg)
    {
        if ($arg != '') {
            if ($j > 1) {
                $result .= ', ' . $arg;
            }
            else {
                $result .= $arg;
            }
            ++$j;
        }
    }
    return $result;
}

/**
 * Join together $args[1-N] with $arg[0]
 * @param <type> $args
 * @return <type>
 */
function exprmgr_implode($args)
{
    if (count($args) <= 1)
    {
        return "";
    }
    $joiner = array_shift($args);
    return implode($joiner,$args);
}

/**
 * Return true if the variable is NULL or blank.
 * @param <type> $arg
 * @return <type>
 */
function exprmgr_empty($arg)
{
    if ($arg === NULL || $arg === "" || $arg === false) {
        return true;
    }
    return false;
}

/**
 * Compute the Sample Standard Deviation of a set of numbers ($args[0-N])
 * @param <type> $args
 * @return <type>
 */
function exprmgr_stddev($args)
{
    $vals = array();
    foreach ($args as $arg)
    {
        if (is_numeric($arg)) {
            $vals[] = $arg;
        }
    }
    $count = count($vals);
    if ($count <= 1) {
        return 0;   // what should default value be?
    }
    $sum = 0;
    foreach ($vals as $val) {
        $sum += $val;
    }
    $mean = $sum / $count;

    $sumsqmeans = 0;
    foreach ($vals as $val)
    {
        $sumsqmeans += ($val - $mean) * ($val - $mean);
    }
    $stddev = sqrt($sumsqmeans / ($count-1));
    return $stddev;
}

/**
 * Javascript equivalent does not cope well with ENT_QUOTES and related PHP constants, so set default to ENT_QUOTES
 * @param <type> $string
 * @return <type>
 */
function expr_mgr_htmlspecialchars($string)
{
    return htmlspecialchars($string,ENT_QUOTES);
}

/**
 * Javascript equivalent does not cope well with ENT_QUOTES and related PHP constants, so set default to ENT_QUOTES
 * @param <type> $string
 * @return <type>
 */
function expr_mgr_htmlspecialchars_decode($string)
{
    return htmlspecialchars_decode($string,ENT_QUOTES);
}

/**
 * Return true of $input matches the regular expression $pattern
 * @param <type> $pattern
 * @param <type> $input
 * @return <type>
 */
function exprmgr_regexMatch($pattern, $input)
{
    try {
        $result = @preg_match($pattern, $input);
    } catch (Exception $e) {
        $result = false;
        // How should errors be logged?
        echo sprintf($this->gT('Invalid PERL Regular Expression: %s'), htmlspecialchars($pattern));
    }
    return $result;
}

/**
 * Display number with comma as radix separator, if needed
 * @param type $value
 * @return type
 */
function exprmgr_fixnum($value)
{
    if (LimeExpressionManager::usingCommaAsRadix())
    {
        $newval = implode(',',explode('.',$value));
        return $newval;
    }
    return $value;
}

/**
 * Returns true if all non-empty values are unique
 * @param type $args
 */
function exprmgr_unique($args)
{
    $uniqs = array();
    foreach ($args as $arg)
    {
        if (trim($arg)=='')
        {
            continue;   // ignore blank answers
        }
        if (isset($uniqs[$arg]))
        {
            return false;
        }
        $uniqs[$arg]=1;
    }
    return true;
}
?>
