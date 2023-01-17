all: TFfoodplan.zip

TFfoodplan.zip: vendor composer.json README.md TFfoodplanAPI.php TFfoodplanParser.php update.php
	zip -r TFfoodplan.zip $^

.PHONY: all