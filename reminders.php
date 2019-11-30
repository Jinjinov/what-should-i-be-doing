<?php

class Reminder {
    function __construct($id,$title,$dt,$creation_timestamp_msec = null,$done = false) {
        if ($id == null) {
            throw new Exception('Reminder id must not be None');
        }
        $this->id = $id;
        $this->title = $title;
        $this->dt = $dt;
        $this->creation_timestamp_msec = $creation_timestamp_msec;
        $this->done = $done;
    }
}

// https://github.com/googleapis/google-api-php-client

// https://developers.google.com/identity/protocols/OAuth2WebServer

$USER_OAUTH_DATA_FILE = os.path.expanduser('~/.google-reminders-cli-oauth');

function authenticate() {
    /*
    returns an Http instance that already contains the user credentials and is
    ready to make requests to alter user data.
    */
    
    $app_keys = [
        "APP_CLIENT_ID" => "380438262846-qktru2bcctgi6cjiqrqdfajcuarqgnqm.apps.googleusercontent.com",
        "APP_CLIENT_SECRET" => "mDFm3Wx-PCWpa0HdOG9r-GnW"
    ];

    $storage = Storage($USER_OAUTH_DATA_FILE);
    $credentials = $storage.get();
    if ($credentials == null || $credentials.invalid) {
        $credentials = tools.run_flow(
            OAuth2WebServerFlow(
                $app_keys['APP_CLIENT_ID'],
                $app_keys['APP_CLIENT_SECRET'],
                ['https://www.googleapis.com/auth/reminders'],
                'google reminders cli tool'),
            $storage);
    }
    $auth_http = $credentials.authorize(httplib2.Http());
    return $auth_http;
}

function create_reminder_request_body($reminder) {
    $body = (object)[
        '2' => (object)[
            '1' => 7
        ],
        '3' => (object)[
            '2' => $reminder.id
        ],
        '4' => (object)[
            '1' => (object)[
                '2' => $reminder.id
            ],
            '3' => $reminder.title,
            '5' => (object)[
                '1' => $reminder.dt.year,
                '2' => $reminder.dt.month,
                '3' => $reminder.dt.day,
                '4' => (object)[
                    '1' => $reminder.dt.hour,
                    '2' => $reminder.dt.minute,
                    '3' => $reminder.dt.second,
                ]
            ],
            '8' => 0
        ]
    ];
    return json_encode($body);
}

function get_reminder_request_body($reminder_id) {
    $body = (object)['2' => [(object)['2' => $reminder_id]]];
    return json_encode($body);
}

function delete_reminder_request_body($reminder_id) {
    $body = (object)['2' => [(object)['2' => $reminder_id]]];
    return json_encode($body);
}

function list_reminder_request_body($num_reminders, $max_timestamp_msec = 0) {
    /*
    The body corresponds to a request that retrieves a maximum of num_reminders reminders, 
    whose creation timestamp is less than max_timestamp_msec.
    max_timestamp_msec is a unix timestamp in milliseconds. 
    if its value is 0, treat it as current time.
    */
    $body = (object)[
        '5' => 1,  // boolean field: 0 or 1. 0 doesn't work ¯\_(ツ)_/¯
        '6' => $num_reminders,  // number of reminders to retrieve
    ];
    
    if ($max_timestamp_msec) {
        $max_timestamp_msec += int(15 * 3600 * 1000);
        $body['16'] = $max_timestamp_msec;
        /*
        Empirically, when requesting with a certain timestamp, reminders with the given timestamp 
        or even a bit smaller timestamp are not returned. 
        Therefore we increase the timestamp by 15 hours, which seems to solve this...  ~~voodoo~~
        (I wish Google had a normal API for reminders)
        */
    }
    return json_encode($body);
}

function build_reminder($reminder_dict) {
    $r = $reminder_dict;
    try {
        $id = $r['1']['2'];
        $title = $r['3'];
        $year = $r['5']['1'];
        $month = $r['5']['2'];
        $day = $r['5']['3'];
        $hour = $r['5']['4']['1'];
        $minute = $r['5']['4']['2'];
        $second = $r['5']['4']['3'];
        $creation_timestamp_msec = int($r['18']);
        $done = array_key_exists('8', $r) && $r['8'] == 1;
        
        return new Reminder(
            $id,
            $title,
            DateTime($year, $month, $day, $hour, $minute, $second),
            $creation_timestamp_msec,
            $done
        );
    }
    catch (Exception $KeyError) {
        print('build_reminder failed: unrecognized reminder dictionary format');
        return null;
    }
}

$URIs = [
    'create' => 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/create',
    'delete' => 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/delete',
    'get' => 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/get',
    'list' => 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/list'
];

$HEADERS = [
    'content-type' => 'application/json+protobuf',
];

$HTTP_OK = 200;

class RemindersClient {
    function __construct() {
        $this->auth_http = authenticate();
    }
    
    function _report_error($response, $content, $func_name) {
        print("Error in ${func_name}:");
        print("    status code: $response->status");
        print("    content: ${content}");
    }
    
    function create_reminder($reminder) {
        /*
        send a 'create reminder' request.
        returns True upon a successful creation of a reminder
        */
        [$response, $content] = $this->auth_http.request(
            $URIs['create'],
            'POST',
            create_reminder_request_body($reminder),
            $HEADERS
        );
        if ($response.status == HTTP_OK) {
            return true;
        }
        else {
            $this->_report_error($response, $content, 'create_reminder');
            return false;
        }
    }

    function get_reminder($reminder_id) {
        /*
        retrieve information about the reminder with the given id. 
        None if an error occurred
        */
        [$response, $content] = $this->auth_http.request(
            $URIs['get'],
            'POST',
            get_reminder_request_body($reminder_id),
            $HEADERS
        );
        if ($response.status == HTTP_OK) {
            $content_dict = json_decode(content.decode('utf-8'));
            if (!isset($content_dict) || empty($content_dict)) {
                print("Couldn\'t find reminder with id=${reminder_id}");
                return null;
            }
            $reminder_dict = $content_dict['1'][0];
            return build_reminder($reminder_dict);
        }
        else {
            $this->_report_error($response, $content, 'get_reminder');
        }
    }
    
    function delete_reminder($reminder_id) {
        /*
        delete the reminder with the given id.
        Returns True upon a successful deletion
        */
        [$response, $content] = $this->auth_http.request(
            $URIs['delete'],
            'POST',
            delete_reminder_request_body($reminder_id),
            $HEADERS
        );
        if ($response.status == HTTP_OK) {
            return true;
        }
        else {
            $this->_report_error($response, $content, 'delete_reminder');
            return false;
        }
    }
    
    function list_reminders($num_reminders) {
        /*
        returns a list of the last num_reminders created reminders, or
        None if an error occurred
        */
        [$response, $content] = $this->auth_http.request(
            $URIs['list'],
            'POST',
            list_reminder_request_body($num_reminders),
            $HEADERS
        );
        if ($response.status == HTTP_OK) {
            $content_dict = json_decode($content.decode('utf-8'));
            if (!array_key_exists('1', $content_dict)) {
                return [];
            }
            $reminders_dict_list = $content_dict['1'];
            $reminders = [];
            foreach($reminders_dict_list as $reminder_dict) {
                array_push($reminders, build_reminder($reminder_dict));
            }
            return $reminders;
        }
        else {
            $this->_report_error($response, $content, 'list_reminders');
            return null;
        }
    }
}