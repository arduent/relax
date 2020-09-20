package main

import (
    "fmt"
    "os"

    "github.com/stellar/go/keypair"
)

func main() {
    pair, err := keypair.Random()
    if err != nil {
	os.Exit(0)
    }

    fmt.Println(pair.Seed())
    fmt.Println(pair.Address())
}
