/* Widget minimize */
$('.wminimize').click(function(e){
e.preventDefault();
var $wcontent = $(this).parent().parent().next('.content');
if($wcontent.is(':visible'))
{
$(this).removeClass('fa-chevron-circle-up text-primary').addClass('fa-chevron-circle-down text-danger');
}
else
{
$(this).removeClass('fa-chevron-circle-down text-danger').addClass('fa-chevron-circle-up text-primary');
}
$wcontent.slideToggle(300);
});

/* Scroll to Top */
$(".totop").hide();
$(function(){
$(window).scroll(function(){
if($(this).scrollTop()>300)
{
$('.totop').slideDown();
}
else
{
$('.totop').slideUp();
}
});
$('.totop a').click(function (e) {
e.preventDefault();
$('body,html').animate({scrollTop: 0}, 500);
});
});

/* panel close 
$('.pclose').click(function(e){
e.preventDefault();
var $pbox = $(this).parent().parent().parent();
$pbox.hide(100);
});*/

/* Date picker 
$(function() {
$('#datetimepicker1').datetimepicker({
pickTime: false
});
});
$(function() {
$('#datetimepicker2').datetimepicker({
pickDate: false
});
});*/

/* Modal fix 
$('.modal').appendTo($('body'));*/

/*Bootstrap Data table, tooltip and bs switch  init */

$('[data-toggle="tooltip"]').tooltip();
/*
$('[switch="yes"]').bootstrapSwitch();	

$('#bs-table').bdt();*/

//alert ("custom js OK"); //test js 