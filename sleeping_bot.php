<?php
require_once '../tele/autoload.php';
include ('functions.php');

$token = str_replace('bot', '', $bot_id);
$bot = new \TelegramBot\Api\Client($token);

$bot->command('start', function ($message) use ($bot)
{
    $answer = 'Добро пожаловать к Деду Бородеду! Бот умеет всего 2 простые команды - добавлять и удалять расписание включения и отключения комментариев для группы. Для работы добавляйте бота в админы (достаточно только прав для Бана юзеров), в случае если админских прав у бота нет - расписание будет удалено автоматически. Расписание для группы может создать лишь один юзер, после этого удалить его может только тот же самый человек (сделано так из-за того, что невозможно получить список админов после добавления новых настроек анонимности для группы).';
    $bot->sendMessage($message->getChat()
        ->getId() , $answer);
});

$bot->command('help', function ($message) use ($bot)
{
    $answer = 'Команды:' . "\n" . "
    add  - Добавить рассписание без комментов в формате имяГруппы|времяОтключенияКомментов-времяВключенияКомментов (groupname|01:00-09:00, groupname|08:00-01:00) \n" . "
    remove - Удалить рассписание (просто отправьте имя группы) \n";
    $bot->sendMessage($message->getChat()
        ->getId() , $answer);
});

$bot->command('add', function ($message) use ($bot)
{

    file_put_contents('sleeping/' . $message->getChat()
        ->getId() , 'add__');
    $bot->sendMessage($message->getChat()
        ->getId() , 'Добавить рассписание без комментов в формате ' . "\n" . 'имяГруппы|времяОтключения-времяВключения ' . "\n" . '(groupname|01:00-09:00, groupname|08:00-01:00)', '');
});

$bot->command('remove', function ($message) use ($bot)
{

    file_put_contents('sleeping/' . $message->getChat()
        ->getId() , 'remove__');
    $bot->sendMessage($message->getChat()
        ->getId() , 'Удалить рассписание (просто отправьте имя группы)', 'Markdown');
});

$bot->run();

$t = json_decode(file_get_contents('php://input') , true);
if (($t['message']['text'] != '/add') && ($t['message']['text'] != '/remove'))
{
    $current_chat = $t['message']['from']['id'];
    $content = file_get_contents('sleeping/' . $current_chat);
    file_put_contents('sleeping/' . $current_chat, $content . $t['message']['text'] . ';');
    #check_and_write ($current_chat);
    $t = check_and_write($current_chat);
}

function check_and_write($current_chat)
{
    global $bot_id;
    $re = '/(add)__([a-zA-Z0-9\-\_]*)\|([0-9\-\:]*);/m';
    $str = file_get_contents('sleeping/' . $current_chat);
    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

    if ((isset($matches[0][1])) && (isset($matches[0][2])) && ($matches[0][2] != ''))
    {
        $t = explode('-', $matches[0][3]);
        $send_array = array(
            'chat_id' => $current_chat,
            'parse_mode' => '',
            'text' => ''
        );
        //file_get_contents($Send2tele.http_build_query($send_array));
        

        $name = $matches[0][2];
        $name = preg_replace('/[^a-zA-Z0-9\_\-]/i', '', $name);
        $current_count = 1;
        foreach (db_select("select count(id) as cnt FROM `schedule` where `chat_name`= ? and `u`!=?", array(
            $name,
            $current_chat
        )) as $row) $current_count = $row['cnt'];

        if ($current_count > 0)
        {
            $send_array = array(
                'chat_id' => $current_chat,
                'parse_mode' => '',
                'text' => 'Для  канала уже задано расписание, необходимо удалить тем же админом, кто создавал его'
            );
            simple_send($bot_id, $send_array);

            file_put_contents('sleeping/' . $current_chat, '');
            die();
        }

        db_exec("delete FROM `schedule` where `chat_name`= ? and `u`=?", array(
            $name,
            $current_chat
        ));
        if ($t[1]=='00:00') $t[1]='00:01';
        if ($t[0]=='00:00') $t[0]='00:01';
        $current_time = strtotime(date('H:i', time()));
        $open_time = strtotime($t[1]);
        $close_time = strtotime($t[0]);
	
        if ($open_time > $close_time) $close_time = strtotime('+1 day', $close_time);
        $is_scheduled_state = between($current_time, $open_time, $close_time);
        $is_scheduled_state = ($is_scheduled_state) ? 0 : 1;

        db_exec("INSERT INTO `schedule` (`chat_name`, `open_time`, `close_time`, `open`,`u`) VALUES (?,?,?,?,?)", array(
            $name,
            $t[1],
            $t[0],
            $is_scheduled_state,
            $current_chat
        ));

        $send_array = array(
            'chat_id' => $current_chat,
            'parse_mode' => '',
            'text' => 'Добавляем расписание для канала ** ' . $matches[0][2] . ', комменты закрыты с ' . $t[0] . ' по ' . $t[1]
        );
        simple_send($bot_id, $send_array);

        file_put_contents('sleeping/' . $current_chat, '');
    }

    $re = '/(remove)__([a-zA-Z0-9\-\_]*);/m';
    $str = file_get_contents('sleeping/' . $current_chat);
    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);

    if ((isset($matches[0][1])) && (isset($matches[0][2])) && ($matches[0][2] != ''))
    {
        $name = $matches[0][2];
        $name = preg_replace('/[^a-zA-Z0-9\_\-]/i', '', $name);

        $current_count = 1;
        foreach (db_select("select count(id) as cnt FROM `schedule` where `chat_name`= ? and `u`=?", array(
            $name,
            $current_chat
        )) as $row) $current_count = $row['cnt'];

        if ($current_count == 0)
        {
            $send_array = array(
                'chat_id' => $current_chat,
                'parse_mode' => '',
                'text' => 'Нечего удалять либо необходимо удалить тем же админом, кто создавал расписание'
            );
            simple_send($bot_id, $send_array);
            file_put_contents('sleeping/' . $current_chat, '');
            die();
        }

        db_exec("delete FROM `schedule` where `chat_name`= ? and `u`=? ", array(
            $name,
            $current_chat
        ));

        $send_array = array(
            'chat_id' => $current_chat,
            'parse_mode' => '',
            'text' => 'Удаляем расписание для канала ** ' . $matches[0][2]
        );
        simple_send($bot_id, $send_array);
        file_put_contents('sleeping/' . $current_chat, '');

    }

}

?>
