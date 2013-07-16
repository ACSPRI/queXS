/* Javascript for XMPP client based on: http://blog.wolfspelz.de/2010/09/website-chat-made-easy-with-xmpp-and.html */

function OnConnectionStatus(nStatus)
{
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

	//make sure the chat tab is in focus when an incoming messages appears

	//display message as new row in message table
	var html = '<tr><td>' + SUPERVISOR_NAME + '</td><td>' + sBody + '</td></tr>';
	$('#chattable > tbody > tr').eq(0).after(html);

	return true;
}

function OnPresenceStanza(stanza)
{
	var sFrom = $(stanza).attr('from');
	var sBareJid = Strophe.getBareJidFromJid(sFrom);
	var sType = $(stanza).attr('type');
	var sShow = $(stanza).find('show').text();
	//alert(sFrom + ':' + sType  + ':' + sShow + ':' + sBareJid);

	// update status on screen
	if (sType == null || sType == '') {
		sType = 'available';
	}

	switch (sType) {
		case 'available': 
		{
      		} break;

		case 'unavailable': 
		{
      		} break;
	}

	return true;
}
