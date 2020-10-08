<?
require_once 'vendor/autoload.php';
$client = new GuzzleHttp\Client();
include ('functions.php');

$channels = db_select("select * FROM `schedule`", array());

if ($channels) foreach ($channels as $row2)
{

    $gr_name = $row2['chat_name'];
    $group_result = check_group($gr_name);

    if ($group_result)
    {
        $client = new GuzzleHttp\Client();
        $response = $client->get('https://api.telegram.org/' . $bot_id . '/setChatPermissions', [GuzzleHttp\RequestOptions::JSON => $group_result, 'http_errors' => false]);
        if ($response->getStatusCode() == 200)
        {
            $t = json_decode($response->getBody() , true);
        }
        else
        {

            $t = json_decode($response->getBody() , true);

            if ($t['description'] == 'Bad Request: not enough rights to change chat permissions')
            {

                $name = $gr_name;
                foreach (db_select("select * FROM `schedule` where `chat_name`= ? limit 1", array(
                    $name
                )) as $ch) $current = $ch;

                db_exec("delete FROM `schedule` where `chat_name`= ?", array(
                    $name
                ));
                $send_array = array(
                    'chat_id' => $current['u'],
                    'parse_mode' => '',
                    'text' => 'У бота нет админских прав, расписание для канала ' . $current['chat_name'] . ' было удалено'
                );
                simple_send($bot_id, $send_array);

            }

        }

    }
}

function check_group($chat_name)
{

    global $bot_id;

    $global_changes = false;
    $group_array = ['chat_id' => '@', 'permissions' => ["can_send_messages" => false, "can_send_media_messages" => false, "can_send_other_messages" => false, "can_add_web_page_previews" => false, "can_invite_users" => false]];
    date_default_timezone_set('Europe/Minsk');

    $current_time = strtotime(date('H:i', time()));
   // $current_time = strtotime('00:07');
    echo ((string)date('Hi', time()) . '(' . $current_time . ')' . ' - ');
    ##$current_time=strtotime('0102');
    

    $name = $chat_name;
    $name = preg_replace('/[^a-zA-Z0-9\_\-]/i', '', $name);
    if ($name != $chat_name) return false;
    $group_array['chat_id'] .= $name;

    $send_array = array(
        'chat_id' => '@' . $name,
        'parse_mode' => '',
        'text' => ''
    );

    foreach (db_select("SELECT * FROM `schedule` where  `chat_name`= ? limit 1", array(
        $name
    )) as $row)
    {

        $open_time = strtotime($row['open_time']);
        $close_time = strtotime($row['close_time']);
        echo '(' . $row['close_time'] . '-' . $row['open_time'] . ')' . '(' . $close_time . '-' . $open_time . ')';
        if ($open_time > $close_time) $close_time = strtotime('+1 day', $close_time);
        $is_open_now = (boolean)$row['open'];
        $is_scheduled_state = between($current_time, $open_time, $close_time);
        echo ((int)$is_scheduled_state);
        echo ((int)$is_open_now);
        if (($is_open_now) && (!$is_scheduled_state))
        {
            echo $name . ' - Should be closed';
            $global_changes = true;
            foreach ($group_array['permissions'] as $k => $v)
            {
                $group_array['permissions'][$k] = $is_scheduled_state;
            }
            db_exec("update `schedule` set open=0 where `chat_name`= ? ", array(
                $name
            ));

            $send_array['text'] = 'Добрай ночы!';
         //   simple_send($bot_id, $send_array);
        }

        elseif ((!$is_open_now) && ($is_scheduled_state))
        {
            $global_changes = true;
            echo $name . ' - Should be open';
            foreach ($group_array['permissions'] as $k => $v)
            {
                $group_array['permissions'][$k] = $is_scheduled_state;
            }
            db_exec("update `schedule` set open=1 where `chat_name`= ? ", array(
                $name
            ));

            $send_array['text'] = 'Добрай ранiцы!';
           // simple_send($bot_id, $send_array);

        }
        else echo $name . ' - No need changes';

    }

    //echo '<pre>';
    if ($global_changes) return ($group_array);
    else return false;

}

?>
