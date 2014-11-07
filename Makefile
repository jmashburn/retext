

COMPOSER = $(shell which composer)
ifeq ($(strip $(COMPOSER)), )
	COMPOSER = php composer.phar
endif

all: test


test-install:
	$(COMPOSER) install

install:

test:
	@PATH=vendor/bin:$(PATH) phpunit;

.PHONY: all test-install install test