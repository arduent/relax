package main

import (
    "fmt"
    "os"
    "encoding/json"

    horizon "github.com/stellar/go/clients/horizonclient"
)

func main() {

    arg := os.Args[1]
    
    client := horizon.DefaultPublicNetClient

    accountRequest := horizon.AccountRequest{AccountID: arg}

    account, err := client.AccountDetail(accountRequest)
    if err != nil {
	os.Exit(0)
    }

    prettyJSON, err := json.MarshalIndent(account.Balances,"","    ")
    fmt.Printf("%s\n",string(prettyJSON))
}

