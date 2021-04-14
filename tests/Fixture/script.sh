#!/usr/bin/env php
<?php

fwrite(STDOUT, "non-error output\n");
fwrite(STDERR, "error output\n");

exit(1);
