<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "mail_db"; 

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipient = $_POST['recipient'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); 
    }

    $saved_file_paths = [];

    // 1. Process File/Document Attachments Loop
    if (!empty($_FILES['document_attachments']['name'][0])) {
        $doc_count = count($_FILES['document_attachments']['name']);

        for ($i = 0; $i < $doc_count; $i++) {
            if ($_FILES['document_attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['document_attachments']['name'][$i]);
                $tmp_name = $_FILES['document_attachments']['tmp_name'][$i];
                
                $unique_filename = time() . "_doc_" . $original_name;
                $target_file_path = $upload_dir . $unique_filename;

                if (move_uploaded_file($tmp_name, $target_file_path)) {
                    $saved_file_paths[] = $target_file_path;
                }
            }
        }
    }

    // 2. Process Image Attachments Loop
    if (!empty($_FILES['image_attachments']['name'][0])) {
        $img_count = count($_FILES['image_attachments']['name']);

        for ($i = 0; $i < $img_count; $i++) {
            if ($_FILES['image_attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $original_name = basename($_FILES['image_attachments']['name'][$i]);
                $tmp_name = $_FILES['image_attachments']['tmp_name'][$i];
                
                $unique_filename = time() . "_img_" . $original_name;
                $target_file_path = $upload_dir . $unique_filename;

                if (move_uploaded_file($tmp_name, $target_file_path)) {
                    $saved_file_paths[] = $target_file_path;
                }
            }
        }
    }

    // Combine all saved target paths into a single comma-separated string
    $file_paths_string = implode(",", $saved_file_paths);

    // SQL Injection Protected Prepared Statement Execution
    $stmt = $conn->prepare("INSERT INTO email_messages (recipient, subject, message_body, uploaded_files) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $recipient, $subject, $message, $file_paths_string);

    if ($stmt->execute()) {
        echo "<script>
                alert('Email sent and files uploaded successfully!');
                window.location.href = 'index.html';
              </script>";
    } else {
        echo "Error saving data record to database: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>