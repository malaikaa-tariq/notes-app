<?php
include 'db.php';

header("Content-Type: application/json");

$action = $_GET['action'] ?? '';

if ($action === "get") {
    $search = $_GET['search'] ?? '';
    $tag = $_GET['tag'] ?? '';

    $sql = "SELECT * FROM notes WHERE 1";

    if ($search) {
        $sql .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";
    }

    if ($tag) {
        $sql .= " AND tags LIKE '%$tag%'";
    }

    $sql .= " ORDER BY created_at DESC";

    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}

if ($action === "create") {
    $d = json_decode(file_get_contents("php://input"), true);
    $conn->query("INSERT INTO notes (title, content, tags) VALUES ('{$d['title']}', '{$d['content']}', '{$d['tags']}')");
    echo json_encode(["status - api.php:37"=>"ok"]);
}

if ($action === "delete") {
    $id = $_GET['id'];
    $conn->query("DELETE FROM notes WHERE id=$id");
    echo json_encode(["status - api.php:43"=>"ok"]);
}

if ($action === "update") {
    $d = json_decode(file_get_contents("php://input"), true);
    $conn->query("UPDATE notes SET title='{$d['title']}', content='{$d['content']}', tags='{$d['tags']}' WHERE id={$d['id']}");
    echo json_encode(["status - api.php:49"=>"ok"]);
}
?>