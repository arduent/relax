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

    arg := os.Args[1]
    
    client := horizon.DefaultPublicNetClient

    kp, _ := keypair.Parse(arg)
    ar := horizon.AccountRequest{AccountID: kp.Address()}

    sourceAccount, err := client.AccountDetail(ar)
    if err != nil {
	os.Exit(0)
    }

    pop := txnbuild.ChangeTrust{
        Line:       txnbuild.CreditAsset{"Vacation","GAPM2QAOLJGDJHCH6NGU4HVHWZPOBWUTYGA7CPS5ZLBE5PHOF7U6V2MI"},
	Limit:      txnbuild.MaxTrustlineLimit,
    }

    ptx, err := txnbuild.NewTransaction(                                               
        txnbuild.TransactionParams{                                                   
            SourceAccount:        &sourceAccount,                                     
            IncrementSequenceNum: true,                                               
            Operations:           []txnbuild.Operation{&pop},                          
            BaseFee:              txnbuild.MinBaseFee,                                
            Timebounds:           txnbuild.NewInfiniteTimeout(),                      
        },                                                                            
    )                                                                                 
                                                                                      
    if err != nil {                                             
        os.Exit(0)                                          
    }                                                           
                                                                
    ptx, err = ptx.Sign(network.PublicNetworkPassphrase, kp.(*keypair.Full))
                                                                          
    if err != nil {                                                       
        os.Exit(0)                                                        
    }                                                                     

    presult, err := client.SubmitTransaction(ptx)                                                                   
    if err != nil {                                                                                               
        fmt.Println(err)                                                                                          
		if herr2, ok := err.(*horizon.Error); ok {
			fmt.Println("Error has additional info")
			fmt.Println(herr2.ResultCodes())
			fmt.Println(herr2.ResultString())
			fmt.Println(herr2.Problem)
		}

    }                                                                                                             
                                                                                                                  
    fmt.Println(presult) 
                                                                
    ptxe, err := ptx.Base64()                                               
    fmt.Println(ptxe) 

}

