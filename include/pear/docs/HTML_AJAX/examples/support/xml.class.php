<?php
/**
 * Test class used in xml examples - notice we have a dom(php5) and a domxml(php4) version
 * 
 * @category   HTML
 * @package    AJAX
 * @author     Elizabeth Smith <auroraeosrose@gmail.com>
 * @copyright  2005-2006 Elizabeth Smith
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
class TestXml {
	function createHealthy()
	{
		if(extension_loaded('Dom'))
		{
			$dom = new DOMDocument('1.0', 'utf-8');
			$root = $dom->createElement('root');
			$root = $dom->appendChild($root);
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'fruit');
			$element->appendChild($dom->createTextNode('peach'));
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'fruit');
			$element->appendChild($dom->createTextNode('plum'));
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'vegetable');
			$element->appendChild($dom->createTextNode('carrot'));
			return $dom;
		}
		elseif (extension_loaded('Domxml'))
		{
			$dom = domxml_new_doc('1.0');
			$element = $dom->create_element('root');
			$root = $dom->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'fruit');
			$element->set_content('peach');
			$root->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'fruit');
			$element->set_content('plum');
			$root->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'vegetable');
			$element->set_content('carrot');
			$root->append_child($element);
			return $dom;
		}
		else {
			return 'No Dom Support';
		}
	}

	function createJunk()
	{
		if(extension_loaded('Dom'))
		{
			$dom = new DOMDocument('1.0', 'utf-8');
			$root = $dom->createElement('root');
			$root = $dom->appendChild($root);
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'drink');
			$element->appendChild($dom->createTextNode('coke'));
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'drink');
			$element->appendChild($dom->createTextNode('beer'));
			$element = $dom->createElement('item');
			$element = $root->appendChild($element);
			$element->setAttribute('type', 'dessert');
			$element->appendChild($dom->createTextNode('pie'));
			return $dom;
		}
		else if(extension_loaded('Domxml'))
		{
			$dom = domxml_new_doc('1.0');
			$element = $dom->create_element('root');
			$root = $dom->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'fruit');
			$element->set_content('peach');
			$root->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'fruit');
			$element->set_content('plum');
			$root->append_child($element);
			$element = $dom->create_element('item');
			$element->set_attribute('type', 'vegetable');
			$element->set_content('carrot');
			$root->append_child($element);
			return $dom;
		}
		else {
			return 'No Dom Support';
		}
	}

	function writeDoc($dom) {
		if(extension_loaded('Dom'))
		{
			// save implementation is broken in dom right now
			file_put_contents('test.xml', $dom->saveXML());
		}
		else if(extension_loaded('Domxml'))
		{
			$doc->dump_file(realpath('test.xml'),false,true);
		}
		else {
			return 'No Dom Support';
		}
	}
}
?>
