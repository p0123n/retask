#!/sevabot
"""

    Simple group chat task manager.

    This also serves as an example how to write stateful handlers.

"""

from __future__ import unicode_literals

import subprocess
from threading import Timer
from datetime import datetime
import os
import logging
import pickle
from collections import OrderedDict

from sevabot.bot.stateful import StatefulSkypeHandler
from sevabot.utils import ensure_unicode, get_chat_id

import base64

logger = logging.getLogger("Retask")

# Set to debug only during dev
logger.setLevel(logging.DEBUG)
logger.debug("Retask module level load import")

class RetaskHandler(StatefulSkypeHandler):
    """
    Skype message handler class for the task manager.
    """

    def __init__(self):
        """Use `init` method to initialize a handler.
        """
        logger.debug("Tasks constructed")

    def init(self, sevabot):
        """
        Set-up our state. This is called

        :param skype: Handle to Skype4Py instance
        """
        logger.debug("Tasks init")

    def handle_message(self, msg, status):
        """Override this method to customize a handler.
        """

        # Skype API may give different encodings
        # on different platforms
        body = ensure_unicode(msg.Body)

        logger.debug("Tasks handler got: %s" % body)

        # Parse the chat message to commanding part and arguments
        words = body.split(" ")
        lower = body.lower()

        if len(words) == 0:
            return False

        # Parse argument for two part command names
        if len(words) >= 2:
            desc = " ".join(words[2:])
        else:
            desc = None

        if lower.startswith("retask"):
            batcmd = "/usr/bin/php -f /home/vnc/retask/sevabot/base.php %s %s %s" % (msg.Sender.Handle, base64.b64encode(msg.Sender.FullName.encode('utf-8')), base64.b64encode(body.encode('utf-8')))
            result = subprocess.check_output(batcmd, shell=True)
            msg.Chat.SendMessage(result.decode('utf-8'))
            return True

        return False


# Export the instance to Sevabot
sevabot_handler = RetaskHandler()

__all__ = ["sevabot_handler"]
