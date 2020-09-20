package main

import (
    "fmt"
    "os"
    "log"
    "encoding/json"

    horizon "github.com/stellar/go/clients/horizonclient"
)

func main() {

    arg := os.Args[1]
    pg := ""
    dir := horizon.OrderAsc

if len(os.Args) > 2 {
    pg = os.Args[2]
}
if len(os.Args) > 3 {
    if os.Args[3] == "desc" {
	dir = horizon.OrderDesc
    }
}
    
    client := horizon.DefaultPublicNetClient

if pg != "" {
    txRequest := horizon.TransactionRequest{ForAccount: arg, Order: dir, Cursor: pg, Limit: 24 }
    txs, err := client.Transactions(txRequest)
    if err != nil {
        log.Fatal(err)
    }

    prettyJSON, err := json.MarshalIndent(txs, "", "    ")
    fmt.Printf("%s\n", string(prettyJSON))
} else {
    txRequest := horizon.TransactionRequest{ForAccount: arg, Order: horizon.OrderAsc, Limit: 24 }
    txs, err := client.Transactions(txRequest)
    if err != nil {
        log.Fatal(err)
    }

    prettyJSON, err := json.MarshalIndent(txs, "", "    ")
    fmt.Printf("%s\n", string(prettyJSON))
}

}

