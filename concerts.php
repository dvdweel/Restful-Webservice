<?php
require("settings.php");

$method = ($_SERVER['REQUEST_METHOD']);
$accept = $_SERVER['HTTP_ACCEPT'];

if (isset($_SERVER['CONTENT_TYPE'])) {
    $content = $_SERVER['CONTENT_TYPE'];
} else {
    $content = '';
}

$self = "https://stud.hosted.hr.nl/0886813/Eindopdracht_rest/concerts/";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

$totalquery = mysqli_query($conn, "SELECT * FROM concerts");
$total = mysqli_num_rows($totalquery);

if (isset($_GET['limit'])) {
    $limit = $_GET['limit'];
} else {
    $limit = $total;
}

$pages = ceil($total / $limit);
$page = min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
    'options' => array(
        'default' => 1,
        'min_range' => 1,
    ),
)));

$offset = ($page - 1) * $limit;
$end = min(($offset + $limit), $total);

if (isset($_GET['start'])) {
    $start = $_GET['start'];
    $page = ceil($start / $limit);
    if ($page == $pages) {
        $next = $pages;
        $nextstart = ($pages - 1) * $limit + 1;
    } else {
        $next = $page + 1;
        $nextstart = $start + $limit;
    }
    if ($page - 1 == 0) {
        $previous = 1;
        $prevstart = 1;
    } else {
        $previous = $page - 1;
        $prevstart = $start - $limit;
    }
} else {
    if ($page == $pages) {
        $next = 1;
        $nextstart = 1;
    } else {
        $next = 2;
        $nextstart = $limit + 1;
    }
    $previous = 1;
    $prevstart = 1;
}

switch ($method) {
    case "GET":
        $query = "SELECT * FROM concerts";
        if (isset($_GET['id'])) {
            $query .= " WHERE id = " . $id;
        } elseif (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
            $query .= " LIMIT $limit";
        }
        $result = mysqli_query($conn, $query);

        $the_array = [];
        $items = [];
        $links = [];
        $pagination = [];

        while ($item = mysqli_fetch_assoc($result)) {
            array_push($items, [
                'id' => $item['id'],
                'artist' => $item['artist'],
                'tour' => $item['tour'],
                'place' => $item['place'],
                'city' => $item['city'],
                'links' => Array(
                    Array('rel' => 'self', 'href' => $self . $item['id']),
                    Array('rel' => 'collection', 'href' => $self)
                )
            ]);
        }
        $links = [
            Array('rel' => 'self', 'href' => $self)];

        if (isset($_GET['limit'])) {
            $last = ($pages - 1) * $limit + 1;
            $pagination = [
                'currentPage' => $page,
                'currentItems' => $end,
                'totalPages' => $pages,
                'totalItems' => $total,
                'links' => Array(
                    Array('rel' => 'first', 'page' => 1, 'href' => $self . '?start=1&limit=' . $limit),
                    Array('rel' => 'last', 'page' => $pages, 'href' => $self . '?start=' . $last . '&limit=' . $limit),
                    Array('rel' => 'previous', 'page' => $previous, 'href' => $self . '?start=' . $prevstart . '&limit=' . $limit),
                    Array('rel' => 'next', 'page' => $next, 'href' => $self . '?start=' . $nextstart . '&limit=' . $limit)
                )];
        } else {
            $pagination = [
                'currentPage' => $page,
                'currentItems' => $total,
                'totalPages' => $pages,
                'totalItems' => $total,
                'links' => Array(
                    Array('rel' => 'first', 'page' => 1, 'href' => $self),
                    Array('rel' => 'last', 'page' => $pages, 'href' => $self),
                    Array('rel' => 'previous', 'page' => $previous, 'href' => $self),
                    Array('rel' => 'next', 'page' => $next, 'href' => $self)
                )];
        }

        $the_array['items'] = $items;
        $the_array['links'] = $links;
        $the_array['pagination'] = $pagination;

        if ($accept == "application/json") {
            header("Content-Type: application/json");
            if (mysqli_num_rows($result) > 0) {
                if (isset($_GET['id'])) {
                    echo json_encode($items[0]);
                } else {
                    echo json_encode($the_array);
                }
            } else {
                http_response_code(404);
            }

        } else if ($accept == "application/xml") {
            header("Content-Type: application/xml");
            if (mysqli_num_rows($result) > 0) {
                function array_to_xml($data, &$xml_data)
                {
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            if (is_numeric($key)) {
                                $key = 'item';
                            }
                            $subnode = $xml_data->addChild($key);
                            array_to_xml($value, $subnode);
                        } else {
                            $xml_data->addChild("$key", htmlspecialchars("$value"));
                        }
                    }
                }

                if (!empty($id)) {
                    $end_array_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><concert></concert>');
                } else {
                    $end_array_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><concerts></concerts>');
                }
                if (isset($_GET['id'])) {
                    array_to_xml($items[0], $end_array_xml);
                    echo $end_array_xml->saveXML();
                } else {
                    array_to_xml($the_array, $end_array_xml);
                    echo $end_array_xml->saveXML();
                }
            } else {
                http_response_code(404);
            }
        } else {
            http_response_code(415);
        }
        break;
    case "POST":
        if ($content == "application/json") {
            $data = json_decode(file_get_contents("php://input"), true);

            $requiredFields = array("artist", "tour", "place", "city");

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    http_response_code(400);
                    echo "Niet alle velden zijn ingevuld.";
                    exit;
                }
            }
            $query = "INSERT INTO concerts (id, artist, tour, place, city) VALUES ('', '{$data['artist']}', '{$data['tour']}', '{$data['place']}', '{$data['city']}')";
            $result = mysqli_query($conn, $query);
            echo "Concert toegevoegd.";
            http_response_code(201);
        } else {
            $data = file_get_contents("php://input");
            $array = array();
            $checker = 0;
            foreach (explode('&', $data) as $val) {
                $result = explode('=', $val);

                if (isset($result[1]) == '') {
                    echo "Niet alle velden zijn ingevuld.";
                } else {
                    $array[$result[0]] = urldecode($result[1]);
                    $checker++;
                }
            }
            if ($checker == 4) {
                $artist = $array['artist'];
                $tour = $array['tour'];
                $place = $array['place'];
                $city = $array['city'];
                $query = "INSERT INTO concerts (id, artist, tour, place, city) VALUES ('', '$artist', '$tour', '$place', '$city')";
                $sql = mysqli_query($conn, $query);
                http_response_code(201);
            } else {
                http_response_code(400);
            }
        }
        break;
    case "DELETE":
        if (isset($_GET['id'])) {
            $query = "DELETE FROM concerts WHERE id = $id";
            $result = mysqli_query($conn, $query);
            http_response_code(204);
        } else {
            http_response_code(403);
        }
        break;
    case "PUT":
        if (!isset($id)) {
            http_response_code(405);
            exit;
        }
        $data = json_decode(file_get_contents("php://input"), true);

        $requiredFields = array("id", "artist", "tour", "place", "city");

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo $field . " niet ingevuld";
                exit;
            }
        }
        $query = "UPDATE `concerts` SET artist = '{$data['artist']}', tour = '{$data['tour']}', place = '{$data['place']}', city = '{$data['city']}'
                  WHERE id = {$data['id']} ";
        $result = mysqli_query($conn, $query);
        break;
    case "OPTIONS";
        if (isset($id)) {
            header('Allow: GET, OPTIONS, PUT, DELETE');
        } else {
            header('Allow: GET, POST, OPTIONS');
        }
        break;
}

