package main

import (
    "fmt"
    "os"

    horizon "github.com/stellar/go/clients/horizonclient"
    "github.com/stellar/go/txnbuild"
    "github.com/stellar/go/keypair"
    "github.com/stellar/go/network"
)

func main() {

    sk := os.Args[1]
    arg := os.Args[2]
    
    client := horizon.DefaultPublicNetClient

    kp, _ := keypair.Parse(sk)
    ar := horizon.AccountRequest{AccountID: kp.Address()}

    sourceAccount, err := client.AccountDetail(ar)
    if err != nil {
	os.Exit(0)
    }

    op := txnbuild.CreateAccount{
        Destination: arg,
        Amount:      "2",
    }
    
    tx, err := txnbuild.NewTransaction(
        txnbuild.TransactionParams{
            SourceAccount:        &sourceAccount,
            IncrementSequenceNum: true,
            Operations:           []txnbuild.Operation{&op},
            BaseFee:              txnbuild.MinBaseFee,
            Timebounds:           txnbuild.NewInfiniteTimeout(), 
        },
    )

    if err != nil {                                     
        os.Exit(0) 
    }

    tx, err = tx.Sign(network.PublicNetworkPassphrase, kp.(*keypair.Full))

    if err != nil {                                                  
        os.Exit(0)                                 
    }

    result, err := client.SubmitTransaction(tx)
    if err != nil {
        fmt.Println(err)
    }

    fmt.Println(result)

}

