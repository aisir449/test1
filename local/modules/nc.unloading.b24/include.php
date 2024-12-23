<?php

\Bitrix\Main\Loader::registerAutoloadClasses(
	"nc.unloading.b24",
	[
		"NC\\UnloadingB24\\Route" => "lib/route.php",
        "NC\\UnloadingB24\\Events" => "lib/events.php",
        "NC\\UnloadingB24\\Logger" => "lib/logger.php",
	]
);

