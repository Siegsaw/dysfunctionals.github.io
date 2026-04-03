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
    "session_destroy();\n",
    "header('Content-Type: application/json');\n",
    "echo json_encode(['success' => true]);\n"
   ],
   "id": "e8bad7010badd16d"
  }
 ],
 "metadata": {},
 "nbformat": 4,
 "nbformat_minor": 5
}
