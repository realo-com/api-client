build-release:
	@rm -f realo-api-client.phar
	@box build -v
	@mv realo-api-client.phar realo-api-client-$(shell git describe --tags --abbrev=0).phar
