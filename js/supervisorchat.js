/* Javascript for XMPP client based on: http://blog.wolfspelz.de/2010/09/website-chat-made-easy-with-xmpp-and.html */

var nConnStatus = "";

function Unload()
{
	if (conn) 
	{
		conn.flush();
		conn.sync = true;
		conn.disconnect();
		conn = null;
	}
}

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

	//check if supervisor on list, if not ask to authenticate
	var roster_iq = $iq({type: "get"}).c('query', {xmlns: Strophe.NS.ROSTER});

	conn.sendIQ(roster_iq, function (iq) {
		if ($(iq).find('item[jid="' + SUPERVISOR_XMPP + '"]').length == 0)
		{
			//request to be on list
			conn.send($pres({ to: SUPERVISOR_XMPP, type: "subscribe" }));
		}
	});

	//send presence message
	conn.send($pres().c('status').t(PRESENCE_MESSAGE));
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

	if(sType == "subscribe" && sBareJid == SUPERVISOR_XMPP)
	{
        	// Send a 'subscribed' notification back to accept the incoming
	        // subscription request if it is the supervisor
		conn.send($pres({ to: SUPERVISOR_XMPP, type: "subscribed" }));
    	}
	else if (sBareJid == SUPERVISOR_XMPP)
	{
		// update status on screen if it is the supervisors status
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

	$(window).bind('beforeunload', function(e) {
	  Unload();
	});
	
	$(window).unload(function()
	{
	  Unload();
	});

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
