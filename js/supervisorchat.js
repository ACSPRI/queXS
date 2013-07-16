/* Javascript for XMPP client based on: http://blog.wolfspelz.de/2010/09/website-chat-made-easy-with-xmpp-and.html */

var nConnStatus = "";

function OnConnectionStatus(nStatus)
{
	nConnStatus = nStatus;

	if (nStatus == Strophe.Status.CONNECTING) {
	} else if (nStatus == Strophe.Status.CONNFAIL) {
	} else if (nStatus == Strophe.Status.DISCONNECTING) {
	} else if (nStatus == Strophe.Status.DISCONNECTED) {
	} else if (nStatus == Strophe.Status.CONNECTED) {
		OnConnected();
	}
}


function OnConnected()
{
	conn.addHandler(OnPresenceStanza, null, "presence");
	conn.addHandler(OnMessageStanza, null, "message");
	conn.send($pres());
}


function OnMessageStanza(stanza)
{
	var sFrom = $(stanza).attr('from');
	var sType = $(stanza).attr('type');
	var sBareJid = Strophe.getBareJidFromJid(sFrom);
	var sBody = $(stanza).find('body').text();
	//alert(sFrom + ':' + sType  + ':' + sBody + ':' + sBareJid);
	// do something, e.g. show sBody with jQuer


	//display message as new row in message table if it is from the supervisor
	if (sBareJid == SUPERVISOR_XMPP)
	{
		//make sure the chat tab is in focus when an incoming messages appears
		
		//find the index of div-supervisorchat (children of tab-main)
		var tm = parent.document.getElementById('tab-main')
		var i = 0;

		for (i=0;i<tm.children.length;i++)
		{
			if (tm.children[i].id == 'tab-chat')
			{
				break;
			}
		}

		tm.tabber.tabShow(i-1);

		var html = '<tr><td>' + SUPERVISOR_NAME + '</td><td>' + sBody + '</td></tr>';
		$('#chattable > tbody > tr').eq(0).after(html);
	}

	return true;
}

function OnPresenceStanza(stanza)
{
	var sFrom = $(stanza).attr('from');
	var sBareJid = Strophe.getBareJidFromJid(sFrom);
	var sType = $(stanza).attr('type');
	var sShow = $(stanza).find('show').text();
	//alert(sFrom + ':' + sType  + ':' + sShow + ':' + sBareJid);

	// update status on screen if it is the supervisors status

	if (sBareJid == SUPERVISOR_XMPP)
	{
		if (sType == null || sType == '') {
			sType = 'available';
		}
	
		switch (sType) {
			case 'available': 
			{
				$('#statusunavailable').hide();
				$('#statusavailable').show();
	
	      		} break;
	
			case 'unavailable': 
			{
				$('#statusavailable').hide();
				$('#statusunavailable').show();
	      		} break;
		}
	}

	return true;
}


function SendChat(chat)
{

	if (chat != '') 
	{
		if (nConnStatus == Strophe.Status.CONNECTED) 
		{
      			var stanza = $msg({ to: SUPERVISOR_XMPP, type: 'chat' }).c('body').t(chat);
			conn.send(stanza.tree());
			var html = '<tr><td>' + MY_NAME + '</td><td>' + chat + '</td></tr>';
			$('#chattable > tbody > tr').eq(0).after(html);
		}
	}
}

$(document).ready(function(){
	$('#chatclick').bind('click', function()
	{
	    SendChat($('#chattext').val());
	    $('#chattext').val('');
	  });
	
	$('#chattext').bind('keypress', function(ev)
	{
	  if (ev.keyCode == 13) {
	    SendChat($('#chattext').val());
	    $('#chattext').val('');
	  }
	});
});
