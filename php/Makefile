.PHONY: test setup launch_server

PWD = $(shell pwd)

TEST_PORT := 51080
API_URL_BASE := http://localhost:$(TEST_PORT)
JSON2BLBH_URL = "$(API_URL_BASE)/json2blbh.php"

TP_DIR = $(PWD)/../tp

TMP_BHIVE = $(PWD)/tmp.blbh

CMD_POST = $(PWD)/post.sh

TEST_OUT = test/tmp.out

EXTRA_DEPENDENCIES = \
	$(PWD)/Makefile \
	$(wildcard $(PWD)/lib/blobhive/*.php) \
	$(wildcard $(PWD)/www/*.*)

test: $(TEST_OUT)/json2blbh/100_normal_object.json.blbh
#	chmod +x $(CMD_POST)
#	$(CMD_POST) -t "$(JSON2BLBH_URL)" -f "$(TP_DIR)/json/100_normal.json"
	
$(TEST_OUT)/json2blbh/%.blbh: $(TP_DIR)/json/% $(EXTRA_DEPENDENCIES)
	@rm -f "$@"
	@mkdir -p $(shell dirname "$@")
	wget \
    	"--header=\"Content-Type: application/json\"" \
    	--post-file=$< \
    	-O "$@" \
    	${JSON2BLBH_URL}
	od -Ax -tx1z "$@"

setup:
	sudo apt install php8.3 php8.3-gd

launch_server:
	@echo "$(API_URL_BASE)"
	php -S "localhost:$(TEST_PORT)" -t www

