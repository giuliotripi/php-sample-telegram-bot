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

require_once '../db_connect.php';
/**
 * 
 * @global string $token - the telegram bot api token
 * @param int $chat_id
 * @param file position/file ID $foto
 * @param bool $reinvio - true if $foto is a foto_id, false if it is a file position
 * @param string $caption
 * @param json $keyboard
 * @return [$sent_sms_id, $sent_photo_id]
 */
function telegram_send_foto($chat_id, $foto, $reinvio = false, $caption = NULL, $keyboard = NULL)
{
    global $token;
    $url = "https://api.telegram.org/bot$token/sendPhoto";
    $cfile = $reinvio ? $foto : "@{$foto}";
    $data = array('photo' => $cfile,  "chat_id" => $chat_id);
    if($caption !== NULL)
        $data["caption"] = $caption;
    if($keyboard != NULL)
        $data["reply_markup"] = $keyboard;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_URL, $url) ;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $out = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($out);
    //print_r($json);
    if($json->{'ok'} == 1)
    {
        $sms_id = $json->{'result'}->{'message_id'};
        $foto_id = $json->{'photo'}[2]->{'file_id'};
        return [$sms_id, $foto_id];
    }
    else
    {
        $error_code = $json->{'error_code'};
        $description = $json->{'description'};
        return false;
    }
}
/**
 * 
 * @global string $token - the telegram bot api token
 * @param type $chat_id
 * @param type $text
 * @param type $keyboard
 * @param type $parse
 * @param type $reply_id
 * @param type $force_reply
 * @return type
 */
function telegram_send_message($chat_id, $text, $keyboard = NULL, $parse = false, $reply_id = NULL, $force_reply = false)
{
    global $token;
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $postinfo = array(
        "chat_id" => $chat_id,
        "text" => $text
    );
    if($parse == true)
        $postinfo["parse_mode"] = "markdown";
    if($reply_id != NULL)
        $postinfo["reply_to_message_id"] = $reply_id;
    if($keyboard != NULL)
        $postinfo["reply_markup"] = $keyboard;
    if($force_reply == true)
        $postinfo["force_reply"] = true;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
    $out = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($out);
    //print_r($json);
    if($json->{'ok'} == 1)
    {
        $sms_id = $json->{'result'}->{'message_id'};
        return array(true, $sms_id);
    }
    else
    {
        $error_code = $json->{'error_code'};
        $description = $json->{'description'};
        return array(-1, $error_code);
    }
}
/**
 * Send a chatAction
 * 
 * @global string $token
 * @param type $chatID
 * @param type $action
 * @return boolean
 */
function telegram_send_action ($chatID, $action)
{
    global $token;
    $url = "https://api.telegram.org/bot$token/sendChatAction";
    $postinfo = array(
        "chat_id" => $chatID,
        "action" => $action
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
    $out = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($out);
    if($json->{'ok'} == 1)
        return true;
    else
        return false;
}
function add_record_sms($mysqli, $id_messaggio, $contatto, $testo, $ricevuto = true)
{
    $ts = time();
    $tipo = $ricevuto ? "IN" : "OUT";
    $testo = escape($testo, $mysqli);
    $query = "INSERT INTO messaggi(ID_MESSAGGIO, CORRISPONDENTE, TESTO, TS, TIPO) VALUES('$id_messaggio', '$contatto', '$testo', '$ts', '$tipo')";
    $mysqli->query($query);
}