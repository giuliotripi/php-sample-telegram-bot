<?php

/* 
 * Copyright (C) 2015 Giulio Tripi <giulio@tripi.eu>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


$post = file_get_contents('php://input');

if(filter_input(INPUT_GET, "pw") != "asfcdasadsafasdgd8648dfgvsIGIGIKUGIUGu")//to avoid malicious requests
    exit();

/**
 * Contains functions like connect_telegram() that create a mysqli link and telegram token
 */
require_once '../db_connect.php';
/**
 * Contains the functions needed to send messages
 */
require_once "send.php";

$json = json_decode($post, true);
$mysqli = connect_telegram();



$sender = addslashes($json["message"]["from"]["id"]);
$nome = addslashes($json["message"]["from"]["first_name"]);
$cognome = addslashes($json["message"]["from"]["last_name"]);
$username = addslashes($json["message"]["from"]["username"]);
$messaggio = addslashes($json["message"]["text"]);
$smsID = addslashes($json["message"]["message_id"]);

$telegram = new telegram_receive($messaggio, $sender);

$telegram->replyID = addslashes($json["message"]["reply_to_message"]["message_id"]);

add_record_sms($mysqli, $smsID, $sender, $messaggio);

$mysqli->query("DELETE FROM azioni WHERE TS < ". (time() - 900));//users have 15 minutes to complete actions

$result = $mysqli->query("SELECT * FROM azioni WHERE ID_TG = '$sender'");
if($mysqli->affected_rows == 0)
    $telegram->scelta_operazione();
else
{
    $row = $result->fetch_assoc();
    $telegram->scelta_operazione($row["AZIONE"], $row["STATO"]);
}

$mysqli->close();
class telegram_receive {
    private $messaggio;
    private $sender;
    public $replyID;
    public $mysqli_tg;
    public function __construct($messaggio, $sender)
    {
        $this->messaggio = $messaggio;
        $this->sender = $sender;
        $this->mysqli_tg = connect_telegram();
    }
    public function scelta_operazione($azione = NULL, $stato = NULL)
    {
        $funzione = ($azione != NULL) ? $azione : $this->messaggio;
        if($stato == NULL && $azione == NULL)//1°
            $valori = NULL;
        elseif($azione != NULL && $stato == NULL)//a volte il 2°
            $valori = false;
        elseif($azione != NULL && $stato != NULL)//2°, 3°, ...
            $valori = json_decode ($stato, true);
        
        if($this->messaggio == "/annulla" || $this->messaggio == "Annulla")
            $this->annulla($valori);
        elseif(!strcasecmp($funzione, "/userid"))
            telegram_send_message($this->sender, "Il tuo ID utente è: {$this->sender}");
        elseif(!strcasecmp($funzione, "/richiediNotifica"))
            $this->richiediNotifica($valori);
        elseif(!strcasecmp($funzione, "/cambiaScuola") || !strcasecmp($funzione, "/start"))
            $this->cambiaScuola($valori);
        elseif(!strcasecmp($funzione, "/feedback"))
            $this->feedback($valori);
        else
            $this->help();
    }
    protected function richiediNotifica($valori)
    {
        $azione = "/richiediNotifica";
        if($valori === NULL)//1° message of this command
        {
            $tastiera = json_encode(array("keyboard" =>  array(array("Annulla"), array("Notifiche Club delle Scienze"), array("Notifiche per classe (studenti)"), array("Notifiche per docente")), "one_time_keyboard"=> true));
            telegram_send_message($this->sender, "Quale tipo di notifica vuoi attivare?", $tastiera);
            set_user_status($this->sender, $azione);
        }
        if($valori === false)//2° message if there are no values in user status
        {
            telegram_send_message($this->sender, "Come ti chiami?");
            $valori["tipo"] = $this->messaggio;
            set_user_status($this->sender, $azione, $valori, true);
        }
        elseif(count($valori) == 1)
        {
            telegram_send_message($this->sender, "Quanti anni hai?");
            $valori["nome"] = $this->messaggio;
            set_user_status($this->sender, $azione, $valori, true);
        }
        elseif(count($valori) == 2)
        {
            telegram_send_message($this->sender, "Quanti anni hai?");
            $valori["anni"] = $this->messaggio;
            set_user_status($this->sender, $azione, $valori, true);
        }
        elseif(count($valori) == 3)
        {
            telegram_send_message($this->sender, "All done!");
            set_user_status($this->sender);//delete current action
            //do whatever you want here
        }
    }
    protected function feedback($valori)
    {
        if($this->messaggio === "/feedback")//doppio controllo per evitare che mandi un feedback con testo "/feedback"
            $valori = NULL;
                
        if($valori === NULL)
        {
            set_user_status($this->sender, "/feedback");
            telegram_send_message($this->sender, "Rispondi con il messaggio da inviare allo sviluppatore oppure /annulla.");
        }
        else
        {
            $mysqli = connect_telegram();
            $ts = time();
            global $smsID;
            $query = "INSERT INTO feedback(ID, ID_TG, TESTO, VERSO, TS) VALUES('$smsID', '$this->sender', '$this->messaggio', 'IN', '$ts')";
            $mysqli->query($query);
            telegram_send_message($this->sender, "Il tuo messaggio è stato registrato. /help");
            set_user_status($this->sender);
        }
    }
    protected function annulla($valori)
    {
        if($valori===NULL)
            telegram_send_message($this->sender, "Nessuna operazione in corso. /help");
        else
        {
            set_user_status($this->sender);
            telegram_send_message($this->sender, "Operazione annullata correttamente. /help");
        }
    }
    protected function help()
    {
        telegram_send_message($this->sender, get_response_message(1));
    }
}
/**
 * Get the text of a message from a database from its ID
 * @global type $mysqli
 * @param type $id
 * @return type
 */
function get_response_message($id)
{
    global $mysqli;
    $result = $mysqli->query("SELECT * FROM risposte WHERE ID_RISPOSTA = '$id'");
    $row = $result->fetch_assoc();
    return $row["TESTO"];
}
/**
 * 
 * @global type $mysqli
 * @param int $id      Id telegram
 * @param str $stato   Stato del comando
 * @param str $azione  Comando in esecuzione
 * @param bool $encode make json_encode
 */
function set_user_status($id, $azione = NULL, $stato = NULL, $to_be_encoded = false)
{
    global $mysqli;
    $ts = time();
    if($to_be_encoded == true)
        $stato = json_encode ($stato);
    $mysqli->query("INSERT INTO azioni (ID_TG, AZIONE, STATO, TS) VALUES ('$id', '$azione', '$stato', '$ts')
  ON DUPLICATE KEY UPDATE AZIONE='$azione', STATO='$stato';");
    if($stato == NULL && $azione == NULL)
        $mysqli->query("DELETE FROM azioni WHERE ID_TG = '$id'");
}
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}
/**
 * Split array values into 2 values per sub-array to have less lines of buttons (2 bts per line) 
 * @param type $elenco
 * @return array
 */
function arrayToKeyboard($elenco)
{
    $key = array(array("Annulla"));
    $temp = array();
    $i=1;
    $tot = count($elenco);
    foreach($elenco as $elemento)
    {
        $temp[] = $elemento;
        if($i%2==0 || $tot == $i)
        {
            $key[] = $temp;
            $temp = array();
        }
        $i++;
    }
    return $key;
}