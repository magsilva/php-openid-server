<?php

require_once('class.jabber.php');

class XEP_0070 extends JABBER
{
    function AuthJID($user, $password, $method, $uri)
    {
        // XXX: better error handling?
        $this->Connect() or die("Couldn't connect!");
        $this->SendAuth() or die ("Couldn't authenticate!");

        // The XEP says that we should send an IQ if the user specified a full
        // JID.  For now we don't.
        $text = "Someone (maybe you) requested an OpenID login at ".
          $uri.". The transaction identifier entered was '".$password.
          ".  If you wish to confirm the request, ".
          "please reply to this message by typing 'OK'.  If not, please ".
          "reply with 'No'.";
        $payload = "<confirm xmlns='http://jabber.org/protocol/http-auth' ".
          "id='".$password."' method='".$method."' ".
          "url='".$uri."'/>";
        $this->bare_jid = ereg_replace("/.*$", "", $user);
        $this->transaction_id = $password;
        $this->SendMessage($user, "normal", NULL, array("body" => $text), $payload);

        // CruiseControl won't do here, since we want to interrupt it when we
        // get an answer.
        $seconds = 30;
        while ($this->connected && $seconds > 0 && !$this->gotanswer) {
            $this->Listen();
            do {
                $packet = $this->GetFirstFromQueue();

                if ($packet) {
                    $this->CallHandler($packet);
                }

            } while (count($this->packet_queue) > 1);

            sleep(1);
            $seconds--;
        }

        if ($this->connected) {
            $this->Disconnect();
        }

        return $this->confirmed;
    }

    var $gotanswer = false;
    var $confirmed = false;
    var $bare_jid;
    var $transaction_id;

    function Handler_message_chat($packet) {
        // Maybe the user's client only allows a reply of type "chat".
        $this->Handler_message_normal($packet);
    }

    function Handler_message_normal($packet) {
        $from = Jabber::GetInfoFromMessageFrom($packet);
        $bare_from = ereg_replace("/.*$", "", $from);
        // XXX: this isn't exactly nodeprep
        $sender_matches = (strtolower($this->bare_jid) == strtolower($bare_from));
        $body = Jabber::GetInfoFromMessageBody($packet);
        $confirm = isset($packet['message']['#']['confirm']);

        $this->AddToLog("body: ".$body);

        $this->gotanswer = true;
        if ($sender_matches && ($confirm || strtoupper(substr($body, 0, 2)) == "OK")) {
            $this->confirmed = true;
        }
        $this->Disconnect();
        $this->connected = false;
    }

    function Handler_message_error($packet) {
        $this->gotanswer = true;
        $this->confirmed = false;
        $this->disconnect();
        $this->connected = false;
    }
}

?>
