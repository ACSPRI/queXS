 
/* Navigation */
var TO = false;
$(document).ready(function(){
$(".content #nav a").on('click',function(e){
if(!$(this).parents(".content:first").hasClass("enlarged")){
if($(this).parent().hasClass("has_sub")) { e.preventDefault(); }
if(!$(this).hasClass("subdrop")) {
// hide any open menus and remove all other classes
$("ul",$(this).parents("ul:first")).slideUp(350);
$("a",$(this).parents("ul:first")).removeClass("subdrop");
// open our new menu and add the open class
$(this).next("ul").slideDown(350);
$(this).addClass("subdrop");
}
else 
if($(this).hasClass("subdrop")) {
$(this).removeClass("subdrop");
$(this).next("ul").slideUp(350);
}
}
});

$(".menubutton").click(function(){
if(!$(".content").hasClass("enlarged")){
$("#nav .has_sub ul").removeAttr("style");
$(".content").addClass("enlarged");
}else{
$(".content").removeClass("enlarged");
}
});

$(".sidebar-dropdown a").on('click',function(e){
e.preventDefault();
if(!$(this).hasClass("open")) {
// hide any open menus and remove all other classes
$(".sidebar #nav").slideUp(350);
$(".sidebar-dropdown a").removeClass("open");
// open our new menu and add the open class
$(".sidebar #nav").slideDown(350);
$(this).addClass("open");
}
else if($(this).hasClass("open")) {
$(this).removeClass("open");
$(".sidebar #nav").slideUp(350);
}
});
});

$('[data-toggle="tooltip"]').tooltip();

$("a[href*='" + location.search + "']").addClass("active");

