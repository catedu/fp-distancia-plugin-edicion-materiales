<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * @param $str
 * @return string
 */
function remove_accents($str): string {
    if (!mb_detect_encoding($str, 'UTF-8', true)) {
        $str = utf8_encode($str);
    }
    return str_replace(
        ['á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä', 'é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë', 'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î', 'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô', 'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü', 'ñ', 'Ñ', 'ç', 'Ç'],
        ['a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'n', 'N', 'c', 'C'],
        $str);
}

/**
 * @return repository_filesystem
 * @throws coding_exception
 * @throws dml_exception
 */
function get_repository(): repository_filesystem {
    $repositoryid = get_config('local_educaaragon', 'repository');
    if ($repositoryid === false) {
        throw new RuntimeException(get_string('no_repository_select', 'local_educaaragon'));
    }
    /** @var repository_filesystem[] $instances */
    $instances = repository::get_instances(['type' => 'filesystem', 'onlyvisible' => false]);
    if (empty($instances) || !isset($instances[$repositoryid])) {
        $context = context_system::instance();
        $repository = new repository_filesystem($repositoryid, $context->id);
        if (empty($repository->instance)) {
            throw new RuntimeException(get_string('no_repository_exists', 'local_educaaragon'));
        }
        return $repository;
    }
    return $instances[$repositoryid];
}

/**
 * @param $src
 * @param $dst
 * @return void
 */
function copy_folder($src, $dst): void {
    $dir = opendir($src);
    if (!mkdir($dst) && !is_dir($dst)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $dst));
    }
    foreach (scandir($src) as $file) {
        if (($file !== '.') && ($file !== '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_folder($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

/**
 * @param $dirPath
 * @return void
 */
function delete_folder($dirPath) {
    if (!is_dir($dirPath)) {
        return;
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) !== '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            delete_folder($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

/**
 * @param string $string
 * @return string
 */
function clean_string(string $string): string {
    $string = str_replace(' ', '-', $string);
    $string = remove_accents($string);
    return (string)preg_replace('/[^A-Za-z\d\-_]/', '', $string);
}

function clean_url(string $url): string {
    $url = str_replace(' ', '%20', $url);
    return preg_replace('/[^A-Za-z\d\-]/', '', $url);
}

/**
 * @param $url
 * @return bool
 */
function is_link_external($url): bool {
    global $CFG;
    $components = parse_url($url);
    $local = parse_url($CFG->wwwroot);
    return !empty($components['host']) && strcasecmp($components['host'], $local['host']);
}

/**
 * @param string $response
 * @return bool
 */
function invalid_http_responses(string $response): bool {
    $responses = [
        'HTTP/1.0 400',
        'HTTP/1.0 401',
        'HTTP/1.0 402',
        'HTTP/1.0 403',
        'HTTP/1.0 404',
        'HTTP/1.0 405',
        'HTTP/1.0 406',
        'HTTP/1.0 407',
        'HTTP/1.0 408',
        'HTTP/1.0 409',
        'HTTP/1.0 410',
        'HTTP/1.0 411',
        'HTTP/1.0 412',
        'HTTP/1.0 413',
        'HTTP/1.0 414',
        'HTTP/1.0 415',
        'HTTP/1.0 416',
        'HTTP/1.0 417',
        'HTTP/1.0 451',
        'HTTP/1.0 500',
        'HTTP/1.0 501',
        'HTTP/1.0 502',
        'HTTP/1.0 503',
        'HTTP/1.0 504',
        'HTTP/1.0 505',
        'HTTP/1.0 511',
        'HTTP/1.1 400',
        'HTTP/1.1 401',
        'HTTP/1.1 402',
        'HTTP/1.1 403',
        'HTTP/1.1 404',
        'HTTP/1.1 405',
        'HTTP/1.1 406',
        'HTTP/1.1 407',
        'HTTP/1.1 408',
        'HTTP/1.1 409',
        'HTTP/1.1 410',
        'HTTP/1.1 411',
        'HTTP/1.1 412',
        'HTTP/1.1 413',
        'HTTP/1.1 414',
        'HTTP/1.1 415',
        'HTTP/1.1 416',
        'HTTP/1.1 417',
        'HTTP/1.1 451',
        'HTTP/1.1 500',
        'HTTP/1.1 501',
        'HTTP/1.1 502',
        'HTTP/1.1 503',
        'HTTP/1.1 504',
        'HTTP/1.1 505',
        'HTTP/1.1 511',
        'HTTP/1.0 400 Bad Request',
        'HTTP/1.0 401 Unauthorized',
        'HTTP/1.0 402 Payment Required',
        'HTTP/1.0 403 Forbidden',
        'HTTP/1.0 404 Not Found',
        'HTTP/1.1 404 No Encontrado',
        'HTTP/1.0 405 Method Not Allowed',
        'HTTP/1.0 406 Not Acceptable',
        'HTTP/1.0 407 Proxy Authentication Required',
        'HTTP/1.0 408 Request Time-out',
        'HTTP/1.0 409 Conflict',
        'HTTP/1.0 410 Gone',
        'HTTP/1.0 411 Length Required',
        'HTTP/1.0 412 Precondition Failed',
        'HTTP/1.0 413 Request Entity Too Large',
        'HTTP/1.0 414 Request-URI Too Large',
        'HTTP/1.0 415 Unsupported Media Type',
        'HTTP/1.0 416 Requested range not satisfiable',
        'HTTP/1.0 417 Expectation Failed',
        'HTTP/1.0 451 Unavailable For Legal Reasons',
        'HTTP/1.0 500 Internal Server Error',
        'HTTP/1.0 501 Not Implemented',
        'HTTP/1.0 502 Bad Gateway',
        'HTTP/1.0 503 Service Unavailable',
        'HTTP/1.0 504 Gateway Time-out',
        'HTTP/1.0 505 HTTP Version not supported',
        'HTTP/1.0 511 Network Authentication Required',
        'HTTP/1.1 400 Bad Request',
        'HTTP/1.1 401 Unauthorized',
        'HTTP/1.1 402 Payment Required',
        'HTTP/1.1 403 Forbidden',
        'HTTP/1.1 404 Not Found',
        'HTTP/1.1 405 Method Not Allowed',
        'HTTP/1.1 406 Not Acceptable',
        'HTTP/1.1 407 Proxy Authentication Required',
        'HTTP/1.1 408 Request Time-out',
        'HTTP/1.1 409 Conflict',
        'HTTP/1.1 410 Gone',
        'HTTP/1.1 411 Length Required',
        'HTTP/1.1 412 Precondition Failed',
        'HTTP/1.1 413 Request Entity Too Large',
        'HTTP/1.1 414 Request-URI Too Large',
        'HTTP/1.1 415 Unsupported Media Type',
        'HTTP/1.1 416 Requested range not satisfiable',
        'HTTP/1.1 417 Expectation Failed',
        'HTTP/1.1 451 Unavailable For Legal Reasons',
        'HTTP/1.1 500 Internal Server Error',
        'HTTP/1.1 501 Not Implemented',
        'HTTP/1.1 502 Bad Gateway',
        'HTTP/1.1 503 Service Unavailable',
        'HTTP/1.1 504 Gateway Time-out',
        'HTTP/1.1 505 HTTP Version not supported',
        'HTTP/1.1 511 Network Authentication Required'
    ];
    return in_array($response, $responses);
}

/**
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_educaaragon_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    global $DB;
    $courseprocessed = $DB->get_record('local_educa_processedcourses', ['courseid' => $course->id]);
    if ($courseprocessed !== false && (int)$courseprocessed->processed === 1) {
        $editables = $DB->get_records('local_educa_editables', ['courseid' => $course->id]);
        if ((count($editables) > 0) && has_capability('local/educaaragon:editresources', $context)) {
            $url = new moodle_url('/local/educaaragon/editables.php', ['courseid' => $course->id]);
            $label = get_string('editables', 'local_educaaragon');
            $icon = new pix_icon('t/editinline', $label);
            $node = navigation_node::create($label, $url, navigation_node::NODETYPE_LEAF, null, null, $icon);
            $parentnode->add_node($node);
        }
    }
}

/*function local_educaaragon_extend_settings_navigation($settingsnav, $context){
    global $CFG;
    if ($context->contextlevel === 70) {

    }
}*/
