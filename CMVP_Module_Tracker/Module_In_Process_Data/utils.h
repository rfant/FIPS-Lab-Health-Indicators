#pragma once
#include <iostream>		// std::cout
#include <string>		// std::string
#include <map>			// std::map
#include <set>			// std::set
#include <string.h>		// strlen
#include <iomanip>		// setw / setfill
#include <stdio.h>


typedef unsigned char byte;

//typedef  char byte;

typedef struct data_t data_t;

struct data_t{
	int word_size; 		// bits per symbol
	int alph_size; 		// symbol alphabet size
	byte maxsymbol; 	// the largest symbol present in the raw data stream
	byte *rawsymbols; 	// raw data words
	byte *symbols; 		// data words
	byte *bsymbols; 	// data words as binary string
	long len; 		// number of words in data
	long blen; 		// number of bits in data
};

using namespace std;



void free_data(data_t *dp){
	if(dp->symbols != NULL) free(dp->symbols);
	if(dp->rawsymbols != NULL) free(dp->rawsymbols);
	if((dp->word_size > 1) && (dp->bsymbols != NULL)) free(dp->bsymbols);
} 

// Read in binary file to test
bool read_file_subset(const char *file_path, data_t *dp, unsigned long subsetIndex, unsigned long subsetSize) {
	FILE *file; 
	int mask, j, max_symbols;
	long rc, i;
	long fileLen;

	//rf: file = fopen(file_path, "rb");
	file = fopen(file_path, "r");
	if(!file){
		printf("Error: could not open '%s'\n", file_path);
		return false;
	}

	rc = (long)fseek(file, 0, SEEK_END);
	if(rc < 0){
		printf("Error: fseek failed\n");
		fclose(file);
		return false;
	}

	fileLen = ftell(file);
	if(fileLen < 0){
		printf("Error: ftell failed\n");
		fclose(file);
		return false;
	}

	rewind(file);

	if(subsetSize == 0) {
		dp->len = fileLen;
	} else {
		rc = (long)fseek(file, subsetIndex*subsetSize, SEEK_SET);
		if(rc < 0){
			printf("Error: fseek failed\n");
			fclose(file);
			return false;
		}

		dp->len = min(fileLen - subsetIndex*subsetSize, subsetSize);
	}

	if(dp->len == 0){
		printf("Error: '%s' is empty\n", file_path);
		fclose(file);
		return false;
	}

	dp->symbols = (byte*)malloc(sizeof(byte)*dp->len);
	dp->rawsymbols = (byte*)malloc(sizeof(byte)*dp->len);
	if((dp->symbols == NULL) || (dp->rawsymbols == NULL)){
		printf("Error: failure to initialize memory for symbols\n");
		fclose(file);
		if(dp->symbols != NULL) {
			free(dp->symbols);
			dp->symbols = NULL;
		}
		if(dp->rawsymbols != NULL) {
			free(dp->rawsymbols);
			dp->rawsymbols = NULL;
		}
		return false;
	}

	rc = fread(dp->symbols, sizeof(byte), dp->len, file);
	if(rc != dp->len){
		printf("Error: file read failure\n");
		fclose(file);
		free(dp->symbols);
		dp->symbols = NULL;
		free(dp->rawsymbols);
		dp->rawsymbols = NULL;
		return false;
	}
	fclose(file);

	memcpy(dp->rawsymbols, dp->symbols, sizeof(byte)* dp->len);
	dp->maxsymbol = 0;

	max_symbols = 1 << dp->word_size;
	int symbol_map_down_table[max_symbols];

	// create symbols (samples) and check if they need to be mapped down
	dp->alph_size = 0;
	memset(symbol_map_down_table, 0, max_symbols*sizeof(int));
	mask = max_symbols-1;
	for(i = 0; i < dp->len; i++){ 
		dp->symbols[i] &= mask;
		if(dp->symbols[i] > dp->maxsymbol) dp->maxsymbol = dp->symbols[i];
		if(symbol_map_down_table[dp->symbols[i]] == 0) symbol_map_down_table[dp->symbols[i]] = 1;
	}

	for(i = 0; i < max_symbols; i++){
		if(symbol_map_down_table[i] != 0) symbol_map_down_table[i] = (byte)dp->alph_size++;
	}

	// create bsymbols (bitstring) using the non-mapped data
	dp->blen = dp->len * dp->word_size;
	if(dp->word_size == 1) dp->bsymbols = dp->symbols;
	else{
		dp->bsymbols = (byte*)malloc(dp->blen);
		if(dp->bsymbols == NULL){
			printf("Error: failure to initialize memory for bsymbols\n");
			free(dp->symbols);
			dp->symbols = NULL;
			free(dp->rawsymbols);
			dp->rawsymbols = NULL;

			return false;
		}

		for(i = 0; i < dp->len; i++){
			for(j = 0; j < dp->word_size; j++){
				dp->bsymbols[i*dp->word_size+j] = (dp->symbols[i] >> (dp->word_size-1-j)) & 0x1;
			}
		}
	}

	// map down symbols if less than 2^bits_per_word unique symbols
	if(dp->alph_size < dp->maxsymbol + 1){
		for(i = 0; i < dp->len; i++) dp->symbols[i] = (byte)symbol_map_down_table[dp->symbols[i]];
	} 

	return true;
}
//====================================================================================


//======================================================================================
bool read_file(const char *file_path, data_t *dp){
	FILE *file; 
	int mask, j, max_symbols;
	long rc, i;

//printf("charlie1\n");

	//rf: file = fopen(file_path, "rb");
	//file = fopen(file_path, "r");
	file = fopen(file_path, "rb");
		
	
	if(!file){
		printf("Error 181: could not open '%s'\n", file_path);
		return false;
	}
//printf("charlie2\n");

	rc = (long)fseek(file, 0, SEEK_END);
	if(rc < 0){
		printf("Error: fseek failed\n");
		fclose(file);
		return false;
	}
//printf("charlie3\n");

	dp->len = ftell(file);
	if(dp->len < 0){
		printf("Error: ftell failed\n");
		fclose(file);
		return false;
	}
//printf("charlie4\n");

	rewind(file);
//printf("charlie5\n");

	if(dp->len == 0){
		printf("Error: '%s' is empty\n", file_path);
		fclose(file);
		return false;
	}
//printf("charlie6\n");

	dp->symbols = (byte*)malloc(sizeof(byte)*dp->len);
	dp->rawsymbols = (byte*)malloc(sizeof(byte)*dp->len);
        if((dp->symbols == NULL) || (dp->rawsymbols == NULL)){
                printf("Error: failure to initialize memory for symbols\n");
                fclose(file);
                if(dp->symbols != NULL) {
                        free(dp->symbols);
                        dp->symbols = NULL;
                }
                if(dp->rawsymbols != NULL) {
                        free(dp->rawsymbols);
                        dp->rawsymbols = NULL;
                }
                return false;
        }
//printf("charlie7\n");

	rc = fread(dp->symbols, sizeof(byte), dp->len, file);
	if(rc != dp->len){
		printf("Error: file read failure\n");
		fclose(file);
		free(dp->symbols);
		dp->symbols = NULL;
		free(dp->rawsymbols);
		dp->rawsymbols = NULL;
		return false;
	}
	fclose(file);

//printf("charlie8\n");

	memcpy(dp->rawsymbols, dp->symbols, sizeof(byte)* dp->len);
	dp->maxsymbol = 0;
//printf("charlie9\n");

	max_symbols = 1 << dp->word_size;
	int symbol_map_down_table[max_symbols];

	// create symbols (samples) and check if they need to be mapped down
	dp->alph_size = 0;
	memset(symbol_map_down_table, 0, max_symbols*sizeof(int));
	mask = max_symbols-1;
	for(i = 0; i < dp->len; i++){ 
		dp->symbols[i] &= mask;
		if(dp->symbols[i] > dp->maxsymbol) dp->maxsymbol = dp->symbols[i];
		if(symbol_map_down_table[dp->symbols[i]] == 0) symbol_map_down_table[dp->symbols[i]] = 1;
	}
//printf("charlieA\n");

	for(i = 0; i < max_symbols; i++){
		if(symbol_map_down_table[i] != 0) symbol_map_down_table[i] = (byte)dp->alph_size++;
	}
//printf("charlieB\n");

/*	// create bsymbols (bitstring) using the non-mapped data
	dp->blen = dp->len * dp->word_size;
	if(dp->word_size == 1) dp->bsymbols = dp->symbols;
	else{
		dp->bsymbols = (byte*)malloc(dp->blen);
		if(dp->bsymbols == NULL){
			printf("Error 267: failure to initialize memory for bsymbols\n");
			free(dp->symbols);
			dp->symbols = NULL;
			free(dp->rawsymbols);
			dp->rawsymbols = NULL;
			return false;
		}
	*/
//printf("charlieC\n");

		//for(i = 0; i < dp->len; i++){
		//	for(j = 0; j < dp->word_size; j++){
		//		dp->bsymbols[i*dp->word_size+j] = (dp->symbols[i] >> (dp->word_size-1-j)) & 0x1;
		//	}
		//}
	//}
//printf("charlieD\n");

	// map down symbols if less than 2^bits_per_word unique symbols
	if(dp->alph_size < dp->maxsymbol + 1){
		for(i = 0; i < dp->len; i++) dp->symbols[i] = (byte)symbol_map_down_table[dp->symbols[i]];
	} 
//printf("charlieE\n");

	return true;
}

