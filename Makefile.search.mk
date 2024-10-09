#################################
Search:

#################################

.PHONY: create-dump enable-vector-store

## Create a new dump
create-dump: compose.yml
	@curl -X POST 'http://localhost:7700/dumps' -H 'Authorization: Bearer 7d9b594befb76b801dd850fd21bc9409174cfc2af41ca3ceda5681ba81f9'

## Enable the vector store
enable-vector-store: compose.yml
	@curl -X PATCH --location "http://localhost:7700/experimental-features/" -H "Content-Type: application/json" -H "Authorization: Bearer 7d9b594befb76b801dd850fd21bc9409174cfc2af41ca3ceda5681ba81f9" -d '{"vectorStore": true}'
