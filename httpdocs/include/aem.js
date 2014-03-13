function updateTranslation(row) { 
	document.getElementById('error_div').innerHTML = "";
	var matchString="";
	for(var i=0;i<numFields;i++){
		matchString = matchString + "("+document.getElementById('row'+row+'field'+i).value+")\\\|";
	}
	matchString = matchString.substr(0,matchString.length-2);
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:updateAction,step:stepId,sequence:row,matchString:matchString,value:document.getElementById('row'+row+'value').value},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			 document.getElementById('error_div').innerHTML = "<pre>XMLHttpRequest="+jqXHR.responseText+"\ntextStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.getElementById('debug').innerHTML = data.result;
			document.getElementById('displayRow'+row+'_edit').style.display="block";
			document.getElementById('displayRow'+row+'_value').style.display="block";
			for(var i=0;i<numFields;i++){
				document.getElementById('displayRow'+row+'Field'+i).style.display="block";
			}
			document.getElementById('editRow'+row+'_submit').style.display="none";
			document.getElementById('editRow'+row+'_value').style.display="none";
			for(var i=0;i<numFields;i++){
				document.getElementById('editRow'+row+'Field'+i).style.display="none";
			}
			//reset the display spans
			document.getElementById('displayRow'+row+'_value').innerHTML=document.getElementById('row'+row+'value').value;
			for(var i=0;i<numFields;i++){
				document.getElementById('displayRow'+row+'Field'+i).innerHTML=document.getElementById('row'+row+'field'+i).value;
			}
			if(updateAction == "insert"){
				lockrow=false;
				document.location.href = actionUrl+"?step="+stepId;
			}
		}   
	});   
} 

function updateTranslationSequence(row, toRow) { 
	document.getElementById('error_div').innerHTML = "";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:"updateSequence",step:stepId,row:row,toRow:toRow},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.location.href = actionUrl+"?step="+stepId;
		}   
	});   
} 

function deleteTranslationRow(row) { 
	document.getElementById('error_div').innerHTML = "";
	var color =	document.getElementById(row).style.backgroundColor
	document.getElementById(row).style.backgroundColor='#FFFF00';
	if(confirm("Are you sure you want to delete this row?")){
		$.ajax({   
			type: "GET",   
			url: actionUrl,   
			cache: false,
			data: {action:"delete",step:stepId,row:row},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = actionUrl+"?step="+stepId;
			}   
		});   
	}else{
		document.getElementById(row).style.backgroundColor=color;
	}
} 
function updateStep(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var matchString="";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:updateAction,step:stepId,sequence:row,token:document.getElementById('row'+row).value},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>XMLHttpRequest="+jqXHR.responseText+"\ntextStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.getElementById('debug').innerHTML = data.result;
			document.getElementById('displayRow'+row+'_edit').style.display="block";
			document.getElementById('displayRow'+row).style.display="block";

			document.getElementById('editRow'+row+'_submit').style.display="none";
			document.getElementById('editRow'+row).style.display="none";
			//reset the display spans
			document.getElementById('displayRow'+row).innerHTML=document.getElementById('row'+row).value;
				lockrow=false;
				document.location.href = actionUrl+"?step="+stepId+"&updating=true";
		}   
	});   
} 

function updateStepSequence(row, toRow) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:"updateSequence",step:stepId,row:row,toRow:toRow},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.location.href = actionUrl+"?step="+stepId+"&updating=true";
		}   
	});   
} 

function deleteStepRow(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var color =	document.getElementById(row).style.backgroundColor
	document.getElementById(row).style.backgroundColor='#FFFF00';
	if(confirm("Are you sure you want to delete this row?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"delete",step:stepId,row:row},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = actionUrl+"?step="+stepId+"&updating=true";
			}   
		});   
	}else{
		document.getElementById(row).style.backgroundColor=color;
	}
} 
function commitStep() { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	if(confirm("Are you sure you want to commit this step?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"commit",step:stepId},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = returnUrl;
			}   
		});   
	}
} 
function cancelStep(force) { 
	document.getElementById('error_div').innerHTML = "";
	if(force || confirm("Are you sure you want to cancel this step?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			async: false,
			url: actionUrl,   
			data: {action:"cancel",step:stepId},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){ 
				if(force){
					cancelComplete=true;
				}else{
					document.location.href = returnUrl;
				}
			}   
		});   
	}
} 
function commitSource() { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	if(confirm("Are you sure you want to commit this path?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"commit",source:sourceId},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = returnUrl;
			}   
		});   
	}
} 
function cancelSource(force) { 
	document.getElementById('error_div').innerHTML = "";
	if(force || confirm("Are you sure you want to cancel this path?")){
		$.ajax({   
			type: "GET", 
			cache: false,
			async: false,  
			url: actionUrl,   
			data: {action:"cancel",source:sourceId},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				if(force){
					cancelComplete=true;
				}else{
					document.location.href = returnUrl;
				}
			}   
		});   
	}
} 
function updateSource(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var matchString="";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:updateAction,source:sourceId,sequence:row,step:document.getElementById('row'+row).value},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>XMLHttpRequest="+jqXHR.responseText+"\ntextStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.getElementById('debug').innerHTML = data.result;
			document.getElementById('displayRow'+row+'_edit').style.display="block";
			document.getElementById('displayRow'+row).style.display="block";

			document.getElementById('editRow'+row+'_submit').style.display="none";
			document.getElementById('editRow'+row).style.display="none";
			//reset the display spans
			document.getElementById('displayRow'+row).innerHTML=document.getElementById('row'+row).value;
				lockrow=false;
				document.location.href = actionUrl+"?source="+sourceId+"&updating=true";
		}   
	});   
} 

function updateSourceSequence(row, toRow) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:"updateSequence",source:sourceId,row:row,toRow:toRow},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.location.href = actionUrl+"?source="+sourceId+"&updating=true";
		}   
	});   
} 

function deleteSourceRow(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var color =	document.getElementById(row).style.backgroundColor
	document.getElementById(row).style.backgroundColor='#FFFF00';
	if(confirm("Are you sure you want to delete this row?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"delete",source:sourceId,row:row},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = actionUrl+"?source="+sourceId+"&updating=true";
			}   
		});   
	}else{
		document.getElementById(row).style.backgroundColor=color;
	}
} 
//DESTINATION PATH
function commitDest() { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	if(confirm("Are you sure you want to commit this path?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"commit",source:sourceId,type:destType},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = returnUrl;
			}   
		});   
	}
} 
function cancelDest(force) { 
	document.getElementById('error_div').innerHTML = "";
	if(force || confirm("Are you sure you want to cancel this path?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"cancel",source:sourceId,type:destType},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				if(force){
					cancelComplete=true;
				}else{
					document.location.href = returnUrl;
				}
			}   
		});   
	}
} 
function updateDest(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var matchString="";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:updateAction,source:sourceId,type:destType,sequence:row,step:document.getElementById('row'+row).value},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>XMLHttpRequest="+jqXHR.responseText+"\ntextStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.getElementById('debug').innerHTML = data.result;
			document.getElementById('displayRow'+row+'_edit').style.display="block";
			document.getElementById('displayRow'+row).style.display="block";

			document.getElementById('editRow'+row+'_submit').style.display="none";
			document.getElementById('editRow'+row).style.display="none";
			//reset the display spans
			document.getElementById('displayRow'+row).innerHTML=document.getElementById('row'+row).value;
				lockrow=false;
				document.location.href = actionUrl+"?source="+sourceId+"&type="+destType+"&updating=true";
		}   
	});   
} 

function updateDestSequence(row, toRow) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	$.ajax({   
		type: "GET",   
		url: actionUrl,   
		data: {action:"updateSequence",source:sourceId,type:destType,row:row,toRow:toRow},   
		dataType: "json",   
		error: function(jqXHR, textStatus, errorThrown){
			leaveWarn=true;
			 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
		},
		success: function(data, textStatus){  
			document.location.href = actionUrl+"?source="+sourceId+"&type="+destType+"&updating=true";
		}   
	});   
} 

function deleteDestRow(row) { 
	leaveWarn=false;
	document.getElementById('error_div').innerHTML = "";
	var color =	document.getElementById(row).style.backgroundColor
	document.getElementById(row).style.backgroundColor='#FFFF00';
	if(confirm("Are you sure you want to delete this row?")){
		$.ajax({   
			type: "GET", 
			cache: false,  
			url: actionUrl,   
			data: {action:"delete",source:sourceId,type:destType,row:row},   
			dataType: "json",   
			error: function(jqXHR, textStatus, errorThrown){
				leaveWarn=true;
				 document.getElementById('error_div').innerHTML = "<pre>textStatus="+textStatus+"\nerrorThrown="+errorThrown+"</pre>";    
			},
			success: function(data, textStatus){  
				document.location.href = actionUrl+"?source="+sourceId+"&type="+destType+"&updating=true";
			}   
		});   
	}else{
		document.getElementById(row).style.backgroundColor=color;
	}
} 

function login(){
	clearMsg();
	var ajaxRequest;  // The variable that makes Ajax possible!
	
	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Internet Explorer Browsers
		try{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				// Something went wrong
				alert("Your browser broke!");
				return false;
			}
		}
	}
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState == 4){
			if(ajaxRequest.responseText == 'SUCCESS'){
			    document.getElementById('guest').style.visibility = "hidden";
			    document.getElementById('guest').style.display = "none";
				document.getElementById('admin').style.visibility = 'visible';
				document.getElementById('admin').style.display = 'block';
//				document.getElementById('msg').innerHTML = ajaxRequest.responseText;
				tb_remove();
				updateAlerts();
			}else{
				document.getElementById('msg').innerHTML = ajaxRequest.responseText;
				tb_remove();
			}
		}
	}
	ajaxRequest.open("GET", actionUrl+"?username="+document.getElementById('username').value+"&password="+document.getElementById('password').value, true);
	ajaxRequest.send(null); 
}
function logout(){
	var ajaxRequest;  // The variable that makes Ajax possible!
	
	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Internet Explorer Browsers
		try{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				// Something went wrong
				alert("Your browser broke!");
				return false;
			}
		}
	}
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState == 4){
			document.location.href = "/index.php";
/*			document.getElementById('admin').style.visibility = 'hidden';
			document.getElementById('admin').style.display = 'none';
			document.getElementById('guest').style.visibility = 'visible';
			document.getElementById('guest').style.display = 'block';
			document.getElementById('password').value="";
			updateAlerts();*/
		}
	}
	ajaxRequest.open("GET", actionUrl+"?action=logout", true);
	ajaxRequest.send(null); 
}
function cancel(){
	return document.location.href=returnUrl;
}
$(document).ready(function() {
	window.onbeforeunload = function() { 
		if((typeof leaveWarn !== 'undefined' && leaveWarn) && (typeof cancelComplete === 'undefined' || !cancelComplete))
			return "If you leave the page without submitting, changes will not be saved. Are you sure?";
	/*	if(confirm("If you leave the page without submitting, changes will not be saved. Are you sure?")){
			eval('cancel'+func+'(true);');
		}else{
			return false;
		} */
	}
	window.onunload = function(){
		if(typeof leaveWarn !== 'undefined' && leaveWarn){
			cancelComplete=false;
			eval('cancel'+func+'(true);');
		}
//		while(!cancelComplete){
			
	}
});
function r_u_there(onoff){
	if(onoff == "on"){
		document.getElementById('bottomscroll').innerHTML = "Did you see this?";
		setTimeout("r_u_there('off')",5000);
	}else{
		document.getElementById('bottomscroll').innerHTML = "";
		setTimeout("r_u_there('on')",30000);
	}
}