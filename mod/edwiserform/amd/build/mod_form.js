"use strict";define(["jquery","local_edwiserform/formviewer"],function(e){return{init:function(){e(document).ready(function(){var o=M.cfg.wwwroot+"/local/edwiserform/view.php?page=newform&mod_edwiserform=true",r="directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=1280,height=800";function i(e){Formeo.dom.multiActions("warning",M.util.get_string("warning","mod_edwiserform"),M.util.get_string("formopen","mod_edwiserform"),[{title:M.util.get_string("discard","mod_edwiserform"),type:"warning",action:function(){createeditform.close(),createeditform=window.open(e,null,r)}},{title:M.util.get_string("wait","mod_edwiserform"),type:"success"}])}window.createeditform=null,window.local_edwiserform_add_new_form=function(o){e("select#id_form").append('<option value="'+o.formid+'">'+o.formname+"</option>"),e("#id_form").val(o.formid),createeditform.close(),createeditform=null},window.local_edwiserform_edit_form=function(o){e('select#id_form option[value="'+o.formid+'"]').text(o.formname),e("#id_form").val(o.formid),createeditform.close(),createeditform=null},e("body").on("click","#preview-form",function(o){o.preventDefault(),window.open(M.cfg.wwwroot+"/local/edwiserform/preview.php?id="+e("#id_form").val(),null,r)}),e("body").on("click","#add-new-form",function(e){e.preventDefault(),null==createeditform||0!=createeditform.closed?createeditform=window.open(o,null,r):i(o)}),e("body").on("click","#edit-form",function(t){var n=o+"&formid="+e("#id_form").val();t.preventDefault(),null==createeditform||0!=createeditform.closed?createeditform=window.open(n,null,r):i(n)}),e("body").on("click","#all-forms",function(e){window.open(M.cfg.wwwroot+"/local/edwiserform/")})})}}});