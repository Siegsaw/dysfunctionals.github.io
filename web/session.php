{
 "cells": [
  {
   "metadata": {},
   "cell_type": "code",
   "outputs": [],
   "execution_count": null,
   "source": [
    "<?php\n",
    "session_start();\n",
    "function requireLogin() {\n",
    "if (!isset($_SESSION['user_id'])) {\n",
    "http_response_code(401);\n",
    "echo json_encode(['success' => false, 'message' => 'Not logged in']);\n",
    "exit;\n",
    "}\n",
    "}\n",
    "?>"
   ],
   "id": "e8bad7010badd16d"
  }
 ],
 "metadata": {},
 "nbformat": 4,
 "nbformat_minor": 5
}
