function Reminder(id,title,dt,creation_timestamp_msec = null,done = false) {
    if (id == null) {
        throw 'Reminder id must not be None';
    }
    this.id = id;
    this.title = title;
    this.dt = dt;
    this.creation_timestamp_msec = creation_timestamp_msec;
    this.done = done;
}

// https://developers.google.com/identity/protocols/OAuth2UserAgent

function create_reminder_request_body(reminder) {
    var body = {
        '2': {
            '1': 7
        },
        '3': {
            '2': reminder.id
        },
        '4': {
            '1': {
                '2': reminder.id
            },
            '3': reminder.title,
            '5': {
                '1': reminder.dt.year,
                '2': reminder.dt.month,
                '3': reminder.dt.day,
                '4': {
                    '1': reminder.dt.hour,
                    '2': reminder.dt.minute,
                    '3': reminder.dt.second,
                }
            },
            '8': 0
        }
    };
    return JSON.stringify(body);
}

function get_reminder_request_body(reminder_id) {
    var body = {'2': [{'2': reminder_id}]};
    return JSON.stringify(body);
}

function delete_reminder_request_body(reminder_id) {
    var body = {'2': [{'2': reminder_id}]};
    return JSON.stringify(body);
}

function list_reminder_request_body(num_reminders, max_timestamp_msec = 0) {
    /*
    The body corresponds to a request that retrieves a maximum of num_reminders reminders, 
    whose creation timestamp is less than max_timestamp_msec.
    max_timestamp_msec is a unix timestamp in milliseconds. 
    if its value is 0, treat it as current time.
    */
    var body = {
        '5': 1,  // boolean field: 0 or 1. 0 doesn't work ¯\_(ツ)_/¯
        '6': num_reminders,  // number of reminders to retrieve
    };
    
    if (max_timestamp_msec) {
        max_timestamp_msec += Number(15 * 3600 * 1000);
        body['16'] = max_timestamp_msec;
        /*
        Empirically, when requesting with a certain timestamp, reminders with the given timestamp 
        or even a bit smaller timestamp are not returned. 
        Therefore we increase the timestamp by 15 hours, which seems to solve this...  ~~voodoo~~
        (I wish Google had a normal API for reminders)
        */
    }
    return JSON.stringify(body);
}

function build_reminder(reminder_dict) {
    var r = reminder_dict;
    try {
        var id = r['1']['2'];
        var title = r['3'];
        var year = r['5']['1'];
        var month = r['5']['2'];
        var day = r['5']['3'];
        var hour = r['5']['4']['1'];
        var minute = r['5']['4']['2'];
        var second = r['5']['4']['3'];
        var creation_timestamp_msec = Number(r['18']);
        var done = '8' in r && r['8'] == 1;
        
        return new Reminder(
            id,
            title,
            Date(year, month, day, hour, minute, second),
            creation_timestamp_msec,
            done
        );
    }
    catch (KeyError) {
        print('build_reminder failed: unrecognized reminder dictionary format');
        return null;
    }
}

var URIs = {
    'create': 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/create',
    'delete': 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/delete',
    'get': 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/get',
    'list': 'https://reminders-pa.clients6.google.com/v1internalOP/reminders/list'
};

var HTTP_OK = 200;
    
function create_reminder(oauth2SignIn, access_token, reminder) {
    /*
    send a 'create reminder' request.
    returns True upon a successful creation of a reminder
    */

    // https://stackoverflow.com/questions/9713058/send-post-data-using-xmlhttprequest

    var xhr = new XMLHttpRequest();

    xhr.open('POST', URIs['create'] + access_token);

    xhr.setRequestHeader('Content-type', 'application/json');
        
    xhr.onreadystatechange = function (e) {
        if (xhr.status == HTTP_OK) {
            return true;
        }
        else {
            oauth2SignIn();
            return false;
        }
    }

    xhr.send(create_reminder_request_body(reminder));
}

function get_reminder(oauth2SignIn, access_token, reminder_id) {
    /*
    retrieve information about the reminder with the given id. 
    None if an error occurred
    */

    // https://stackoverflow.com/questions/9713058/send-post-data-using-xmlhttprequest

    var xhr = new XMLHttpRequest();

    xhr.open('POST', URIs['get'] + access_token);

    xhr.setRequestHeader('Content-type', 'application/json');

    xhr.onreadystatechange = function (e) {
        if (xhr.status == HTTP_OK) {
            var content_dict = JSON.parse(xhr.response.decode('utf-8'));
            if (content_dict == {}) {
                print(`Couldn't find reminder with id=${reminder_id}`);
                return null;
            }
            var reminder_dict = content_dict['1'][0];
            return build_reminder(reminder_dict);
        }
        else {
            oauth2SignIn();
            return null;
        }
    }

    xhr.send(get_reminder_request_body(reminder_id));
}

function delete_reminder(oauth2SignIn, access_token, reminder_id) {
    /*
    delete the reminder with the given id.
    Returns True upon a successful deletion
    */

    // https://stackoverflow.com/questions/9713058/send-post-data-using-xmlhttprequest

    var xhr = new XMLHttpRequest();

    xhr.open('POST', URIs['delete'] + access_token);

    xhr.setRequestHeader('Content-type', 'application/json');

    xhr.onreadystatechange = function (e) {
        if (xhr.status == HTTP_OK) {
            return true;
        }
        else {
            oauth2SignIn();
            return false;
        }
    }

    xhr.send(delete_reminder_request_body(reminder_id));
}

function list_reminders(oauth2SignIn, access_token, num_reminders) {
    /*
    returns a list of the last num_reminders created reminders, or
    None if an error occurred
    */

    // https://stackoverflow.com/questions/9713058/send-post-data-using-xmlhttprequest

    var xhr = new XMLHttpRequest();

    xhr.open('POST', URIs['list'] + access_token);

    xhr.setRequestHeader('Content-type', 'application/json');

    xhr.onreadystatechange = function (e) {
        if (xhr.status == HTTP_OK) {
            var content_dict = JSON.parse(xhr.response.decode('utf-8'));
            if (!('1' in content_dict)) {
                return [];
            }
            var reminders_dict_list = content_dict['1'];
            var reminders = [];
            for(var reminder_dict of reminders_dict_list) {
                reminders.push(build_reminder(reminder_dict));
            }
            return reminders;
        }
        else {
            oauth2SignIn();
            return null;
        }
    }

    xhr.send(list_reminder_request_body(num_reminders));
}
