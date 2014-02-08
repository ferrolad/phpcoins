$(function(){
    $('form').addClass('form-horizontal')
    $('table').addClass('table table-bordered')
    $('div[well]').addClass('well clearfix')
})

$(function(){
    $('form').submit(function(e){
	e.preventDefault();
	var input = $(e.target).find('input[name]')
	var data = {}
	input.each(function(e){
	    var k = $(this).attr('name')
	    if($(this).attr('type') == 'checkbox'){
		if(this.checked){
		    k = k.replace('[]','')
		    data[k] = data[k] || []
		    data[k].push($(this).attr('value'))
		}
		return
	    }
	    data[k] = $(this).val()
	})
	    var textarea = $(e.target).find('textarea')
	textarea.each(function(e){
	    var k = $(this).attr('name')
	    data[k] = $(this).val()
	})
	    console.log(data)
	$.post(url($(this).attr('act')),data,function(d){
	    var d = parse_json(d)
	    msg(d.msg,d.jump)
	})
	return false;
    })
})

function url(act,arg){
    arg = arg || {}
    arg.act = act
    ADMIN && (arg.admin = 1)
    var url = '/?'
    $.each(arg,function(k,v){
	url += k+'='+v+'&'
    })
	return url.slice(0,-1)
}

function msg(msg,jump){
    if('undefined' == typeof msg && 'undefined' != typeof jump){
	location.href = jump
	return
    }
    var html = '<div class="modal fade" id="modal_msg">'+
	'<div class="modal-dialog">'+
	'<div class="modal-content">'+
	'<div class="modal-body">'+
	msg+
	'</div></div></div></div>'
    $(html).appendTo('body')
    $('#modal_msg').modal()
    setTimeout(function(){
	$('#modal_msg').modal('hide');
	setTimeout(function(){
	    $('#modal_msg').remove()
	},1000)
	if(typeof jump != 'undefined')
	    location.href = jump
    },1000)
}

function is_json(str){
    if('undefined' == typeof str)
	return false
    if (/^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').
			     replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
			     replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {
	return true
    }else{
	return false
    }
}

function parse_json(str){
    if(is_json(str)){
	return $.parseJSON(str)
    }else{
	console.log('json bad format')
	console.log(str)
    }
}

