{
 "cells": [
  {
   "metadata": {},
   "cell_type": "code",
   "outputs": [],
   "execution_count": null,
   "source": [
    "<?php\n",
    "header('Content-Type: application/json');\n",
    "require 'db.php';\n",
    "session_start();\n",
    "$data = json_decode(file_get_contents('php://input'), true);\n",
    "$email = trim($data['email'] ?? '');\n",
    "$password = $data['password'] ?? '';\n",
    "$stmt = $conn->prepare('SELECT user_id, username, email, password_hash FROM\n",
    "users WHERE email = ?');\n",
    "$stmt->bind_param('s', $email);\n",
    "$stmt->execute();\n",
    "$result = $stmt->get_result();\n",
    "if ($result->num_rows === 0) {\n",
    "    echo json_encode(['success' => false, 'message' => 'No account found']);\n",
    "    exit;\n",
    "}\n",
    "$user = $result->fetch_assoc();\n",
    "if (!password_verify($password, $user['password_hash'])) {\n",
    "    echo json_encode(['success' => false, 'message' => 'Wrong password']);\n",
    "    exit;\n",
    "}\n",
    "$_SESSION['user_id'] = $user['user_id'];\n",
    "$_SESSION['username'] = $user['username'];\n",
    "$_SESSION['email'] = $user['email'];\n",
    "echo json_encode([\n",
    "    'success' => true,\n",
    "    'username' => $user['username'],\n",
    "    'email' => $user['email']\n",
    "]);\n"
   ],
   "id": "e8bad7010badd16d"
  }
 ],
 "metadata": {},
 "nbformat": 4,
 "nbformat_minor": 5
}
