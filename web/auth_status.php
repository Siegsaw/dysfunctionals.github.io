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
    "session_start();\n",
    "echo json_encode([\n",
    "'loggedIn' => isset($_SESSION['user_id']),\n",
    "'user_id' => $_SESSION['user_id'] ?? null,\n",
    "'username' => $_SESSION['username'] ?? null,\n",
    "'email' => $_SESSION['email'] ?? null\n",
    "]);"
   ],
   "id": "e8bad7010badd16d"
  }
 ],
 "metadata": {},
 "nbformat": 4,
 "nbformat_minor": 5
}
