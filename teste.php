<?php
echo "<pre>";
echo shell_exec("docker stats frigate --no-stream");
echo "</pre>";
