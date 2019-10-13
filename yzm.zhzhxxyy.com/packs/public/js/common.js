var element,form,layer;
layui.use(['element','form','layer'], function(){
	element = layui.element;
	form = layui.form;
	layer = layui.layer;
	//监听网站配置/修改资料表单提交
	form.on('submit(setting)', function(data){
		var index = layer.load(1);
		$.post(data.form.action, data.field, function(data) {
			if(data.error == 0){
				layer.msg('恭喜您，保存成功',{icon:1});
			}else{
				layer.msg(data.info,{icon:2});
			}
			layer.close(index);
		},"json");
		return false;
	});
	form.on('submit(login)', function(data){
		var index = layer.load(1);
		var backurl = $('.layui-form').attr('backurl');
		$.post(data.form.action, data.field, function(data) {
			if(data.error == 0){
				layer.msg('恭喜你，登陆成功！',{icon:1});
				if(backurl==''){
					setTimeout(function(){
						window.location.href = data.info.url;
					},1500);
				}else{
					setTimeout(function(){
						window.location.href = backurl;
					},1500);
				}
			}else{
				layer.msg(data.info,{icon:2});
			}
			layer.close(index);
		},"json");
		return false;
	});
	form.on('submit(edit)', function(data){
		var index = layer.load(1);
		$.post(data.form.action, data.field, function(data) {
			if(data.error == 0){
				layer.msg('恭喜你，操作成功！',{icon:1});
				setTimeout(function(){
					parent.location.reload();
				},1500);
			}else{
				layer.msg(data.info,{icon:2});
			}
			layer.close(index);
		},"json");
		return false;
	});
	//监听批量删除提交
	form.on('submit(del_pl)', function(data){
		if(JSON.stringify(data.field).length<3){
			layer.msg('未选中要删除的数据...',{icon:7});return;
		}
		layer.confirm('确定要删除这些数据吗？', {
			title:'批量删除提示',
		    btn: ['确定', '取消'], //按钮
		    shade:0.001
		}, function(index) {
		    var index = layer.load(1);
			$.post(data.form.action, data.field, function(data) {
				if(data.error == 0){
					layer.msg('恭喜你，操作成功！',{icon:1});
					setTimeout(function(){
						if(typeof(data.info.parent) != undefined && data.info.parent == 1){
							parent.location.href = data.info.url;
						}else{
							location.href = data.info.url;
						}
					},1500);
				}else{
					layer.msg(data.info,{icon:2});
				}
				layer.close(index);
			},"json");
			return false;
		}, function(index) {
		    layer.close(index);
		});
	});
});
//菜单点击事件
function turnLink(which){
	var obj = $(which);
	var link = obj.attr('_href');
	$('#iframe_main').attr('src', link);
}

function mode(m) {
	var mctime = setInterval(function(){
		if(layer !=null && layer.tips != undefined){
			eval(m);
			clearInterval(mctime);
		}
	},100);
}
function getTime(elem,type){
	elem = (elem==''||elem==undefined)?'#kstime':'#'+elem;
	type = (type==''||type==undefined)?'date':type;
	laydate.render({
		elem:elem
		,type:type
	});
}
function select_all(){
	var a = $(".xuan");  
    for(var i = 0; i < a.length; i++) {
        a[i].checked = (a[i].checked) ? false : true;
    }
}
function del_one(url,id){
	if(arguments[2]){
		var title = arguments[2];
	}else{
		var title = '确定要删除该列数据吗？';
	}
	if(!id){
		layer.msg('参数错误，请刷新重试',{icon:2});
	}else{
		layer.confirm(title, {
			title:'删除提示',
		    btn: ['确定', '取消'], //按钮
		    shade:0.001
		}, function(index) {
		    $.post(url, {
				id:id
			}, function(data) {
				if(data.error == 0){
					layer.msg('恭喜你，删除成功',{icon:1});
					$('#row_'+id).remove();
					if(typeof(data.info.turn) != undefined && data.info.turn ==1){
						setTimeout(function(){
							location.href = data.info.url;
						},1500);
					}
				}else{
					layer.msg(data.info,{icon:2});
				}
			},"json");
		}, function(index) {
		    layer.close(index);
		});
	}
}

function get_open(url,title,w,h){
	layer.open({
		title:title,
		type: 2,
		area: [w, h],
		closeBtn: 1, //不显示关闭按钮
		shade: 0.01,
		shadeClose: true, //开启遮罩关闭
		content: url
	});
}

function goto_page(link){
	var page = $('#goto_page').val();
	var purl = link+page;
	location.href = purl;
}