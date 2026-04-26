<?php
$conn = new mysqli("localhost", "root", "", "notes_db");
if ($conn->connect_error) die("DB error");

$action = $_GET['action'] ?? '';

// API
if ($action === "get") {
    $search = $_GET['search'] ?? '';
    $sql = "SELECT * FROM notes WHERE 1";

    if ($search) {
        $sql .= " AND (title LIKE '%$search%' OR content LIKE '%$search%' OR tags LIKE '%$search%')";
    }

    $sql .= " ORDER BY created_at DESC";

    $res = $conn->query($sql);
    $data = [];

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

if ($action === "getOne") {
    $id = $_GET['id'];
    $res = $conn->query("SELECT * FROM notes WHERE id=$id");
    echo json_encode($res->fetch_assoc());
    exit;
}

if ($action === "create" || $action === "update") {
    $d = json_decode(file_get_contents("php://input"), true);

    if ($action === "create") {
        $conn->query("INSERT INTO notes (title, content, tags, color) VALUES ('{$d['title']}', '{$d['content']}', '{$d['tags']}', '{$d['color']}')");
    } else {
        $conn->query("UPDATE notes SET title='{$d['title']}', content='{$d['content']}', tags='{$d['tags']}', color='{$d['color']}' WHERE id={$d['id']}");
    }

    echo json_encode(["status - index.php:46"=>"ok"]);
    exit;
}

if ($action === "delete") {
    $id = $_GET['id'];
    $conn->query("DELETE FROM notes WHERE id=$id");
    echo json_encode(["status - index.php:53"=>"ok"]);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Advanced Notes</title>

<style>
:root {
    --bg: #f9fafb;
    --text: #111827;
    --panel: #ffffff;
    --card: #ffffff;
    --border: #e5e7eb;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
}

/* Layout */
.container {
    display: flex;
    height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 25%;
    background: var(--panel);
    padding: 20px;
    border-right: 1px solid var(--border);
    overflow-y: auto;
}

.sidebar input {
    margin-bottom: 15px;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px;
    font-size: 0.95rem;
}

/* Notes list */
#notes {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

.note {
    padding: 14px;
    border-radius: 10px;
    cursor: pointer;
    background: var(--card);
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: transform 0.15s ease, box-shadow 0.2s ease;
}
.note:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.note b {
    display: block;
    font-size: 1rem;
    margin-bottom: 6px;
    color: #111827;
}
.note small {
    color: #6b7280;
}

/* Editor */
.editor {
    flex: 1;
    padding: 25px;
    background: var(--panel);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.editor h2 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid var(--border);
    padding-bottom: 8px;
}

/* Inputs */
input, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card);
    font-size: 0.95rem;
    color: var(--text);
}
textarea {
    min-height: 120px;
    resize: vertical;
}

/* Buttons */
button {
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s ease, transform 0.1s ease;
}
button:hover {
    transform: scale(1.02);
}
button.save {
    background: #3b82f6;
    color: white;
}
button.delete {
    background: #ef4444;
    color: white;
}

/* Color picker */
.color-box {
    display: inline-block;
    width: 28px;
    height: 28px;
    margin: 5px;
    cursor: pointer;
    border-radius: 50%;
    border: 2px solid var(--border);
    transition: transform 0.2s ease;
}
.color-box:hover {
    transform: scale(1.15);
}

/* View page container */
.view-page {
    display: none;
    max-width: 700px;
    margin: 40px auto;
    padding: 30px;
    border-radius: 12px;
    background: var(--card);
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    animation: fadeIn 0.3s ease;
}

/* Action bar */
.view-page .actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-bottom: 20px;
}
.view-page .actions button {
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: transform 0.1s ease;
}
.view-page .actions button:hover {
    transform: scale(1.05);
}
.view-page .actions button.back { background: #6b7280; color: white; }
.view-page .actions button.edit { background: #f59e0b; color: white; }
.view-page .actions button.save { background: #3b82f6; color: white; }
.view-page .actions button.delete { background: #ef4444; color: white; }

/* Content */
.view-page h1 {
    margin: 0 0 12px;
    font-size: 1.6rem;
    font-weight: 600;
    color: #111827;
}
.view-page p {
    line-height: 1.6;
    font-size: 1rem;
    color: #374151;
    margin-bottom: 15px;
}
.view-page #vTags {
    font-size: 0.85rem;
    color: #374151;
    background: #f3f4f6;
    padding: 6px 12px;
    border-radius: 20px;
    display: inline-block;
}

/* Smooth fade */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

</style>

</head>
<body>

<div class="container" id="mainApp">

<!-- LEFT -->
<div class="sidebar">
    <input id="search" placeholder="Search...">
    <div id="notes"></div>
</div>

<!-- RIGHT -->
<div class="editor">
    <h2>Editor</h2>

    <input id="title" placeholder="Title">
    <textarea id="content"></textarea>
    <input id="tags" placeholder="tags">

    <div>
        Select Color:
        <div class="color-box" style="background:#f87171" onclick="setColor('red')"></div>
        <div class="color-box" style="background:#60a5fa" onclick="setColor('blue')"></div>
        <div class="color-box" style="background:#34d399" onclick="setColor('green')"></div>
        <div class="color-box" style="background:#facc15" onclick="setColor('yellow')"></div>
    </div>

    <input type="hidden" id="noteId">
    <input type="hidden" id="color">

    <button onclick="saveNote()">Save</button>
    <button onclick="deleteNote()">Delete</button>
</div>

</div>

<!-- VIEW PAGE -->
<div class="view-page" id="viewPage">
    <div class="actions">
        <button class="back" onclick="goBack()">Back</button>
        <button class="edit" onclick="enableEdit()">Edit</button>
        <button class="save" onclick="saveEdit()">Save</button>
        <button class="delete" onclick="deleteViewNote()">Delete</button>
    </div>

    <h1 id="vTitle"></h1>
    <p id="vContent"></p>
    <div id="vTags"></div>

    <!-- Hidden edit fields -->
    <input id="editTitle" style="display:none">
    <textarea id="editContent" style="display:none"></textarea>
    <input id="editTags" style="display:none">
    <input type="hidden" id="editId">
    <input type="hidden" id="editColor">
</div>



<script>
let currentColor = "default";



// COLORS
function setColor(c) {
    currentColor = c;
    document.getElementById('color').value = c;
}

// FETCH
function fetchNotes() {
    let s = document.getElementById('search').value;

    fetch(`?action=get&search=${s}`)
    .then(r => r.json())
    .then(data => {
        let html = '';

        data.forEach(n => {
            let bg = getColor(n.color);

            html += `
                <div class="note" style="background:${bg}" onclick="openNote(${n.id})">
                    <b>${n.title}</b><br>
                    ${n.content.substring(0,30)}...
                </div>
            `;
        });

        document.getElementById('notes').innerHTML = html;
    });
}

// COLOR MAP
function getColor(c) {
    return {
        red: "#fecaca",
        blue: "#bfdbfe",
        green: "#bbf7d0",
        yellow: "#fef08a"
    }[c] || "#e5e7eb";
}

// SAVE
function saveNote() {
    let id = document.getElementById('noteId').value;

    let data = {
        id,
        title: title.value,
        content: content.value,
        tags: tags.value,
        color: currentColor
    };

    let action = id ? "update" : "create";

    fetch(`?action=${action}`, {
        method: "POST",
        body: JSON.stringify(data)
    }).then(() => fetchNotes());
}

// DELETE
function deleteNote() {
    let id = document.getElementById('noteId').value;
    if (!id) return;

    fetch(`?action=delete&id=${id}`)
    .then(() => fetchNotes());
}

function deleteViewNote() {
    let id = document.getElementById('editId').value;
    if (!id) return;

    fetch(`?action=delete&id=${id}`)
    .then(() => {
        fetchNotes();
        goBack(); // return to main list after deletion
    });
}


// OPEN VIEW PAGE
function openNote(id) {
    fetch(`?action=getOne&id=${id}`)
    .then(r => r.json())
    .then(n => {
        // Show view page
        document.getElementById('mainApp').style.display = "none";
        document.getElementById('viewPage').style.display = "block";

        document.getElementById('vTitle').innerText = n.title;
        document.getElementById('vContent').innerText = n.content;
        document.getElementById('vTags').innerText = n.tags;
        document.getElementById('viewPage').style.background = getColor(n.color);

        // Pre-fill editor for editing later
        document.getElementById('title').value = n.title;
        document.getElementById('content').value = n.content;
        document.getElementById('tags').value = n.tags;
        document.getElementById('noteId').value = n.id;
        document.getElementById('color').value = n.color;
        currentColor = n.color;
    });
}


// BACK
function goBack() {
    document.getElementById('mainApp').style.display = "flex";
    document.getElementById('viewPage').style.display = "none";
}

function editNote() {
    document.getElementById('mainApp').style.display = "flex";
    document.getElementById('viewPage').style.display = "none";
}

function enableEdit() {
    // Hide display fields
    document.getElementById('vTitle').style.display = "none";
    document.getElementById('vContent').style.display = "none";
    document.getElementById('vTags').style.display = "none";

    // Show edit fields with current values
    document.getElementById('editTitle').style.display = "block";
    document.getElementById('editContent').style.display = "block";
    document.getElementById('editTags').style.display = "block";

    document.getElementById('editTitle').value = document.getElementById('vTitle').innerText;
    document.getElementById('editContent').value = document.getElementById('vContent').innerText;
    document.getElementById('editTags').value = document.getElementById('vTags').innerText;
}

function saveEdit() {
    let data = {
        id: document.getElementById('editId').value,
        title: document.getElementById('editTitle').value,
        content: document.getElementById('editContent').value,
        tags: document.getElementById('editTags').value,
        color: document.getElementById('editColor').value || currentColor
    };

    fetch(`?action=update`, {
        method: "POST",
        body: JSON.stringify(data)
    }).then(() => {
        fetchNotes();
        openNote(data.id);

        // Reset to view mode (hide inputs, show text)
        document.getElementById('editTitle').style.display = "none";
        document.getElementById('editContent').style.display = "none";
        document.getElementById('editTags').style.display = "none";

        document.getElementById('vTitle').style.display = "block";
        document.getElementById('vContent').style.display = "block";
        document.getElementById('vTags').style.display = "block";
    });
}


function openNote(id) {
    fetch(`?action=getOne&id=${id}`)
    .then(r => r.json())
    .then(n => {
        document.getElementById('mainApp').style.display = "none";
        document.getElementById('viewPage').style.display = "block";

        document.getElementById('vTitle').innerText = n.title;
        document.getElementById('vContent').innerText = n.content;
        document.getElementById('vTags').innerText = n.tags;

        // Apply background color
        document.getElementById('viewPage').style.background = getColor(n.color);

        // Keep color for editing
        document.getElementById('editId').value = n.id;
        document.getElementById('editColor').value = n.color;
        currentColor = n.color;
    });
}


search.onkeyup = fetchNotes;

fetchNotes();
</script>

</body>
</html>