package main

import (
    "fmt"
    "os"
    "os/exec"
    "strings"
    "strconv"
//    "encoding/json"

//    horizon "github.com/stellar/go/clients/horizonclient"
)

func main() {

    src := os.Args[1]
    dst := os.Args[2]
    amount := os.Args[3]
    asset := os.Args[4]
    due := os.Args[5]


/*
	convert the src and dest addresses to a 32 byte array
	this gives us 64 bytes in "o" for data in account
	kn.sd
*/

    cmd, err := exec.Command("/usr/local/bin/keystr",src).Output()
    if err != nil {
	fmt.Printf("err\n");
    }
    s := strings.Split(string(cmd),",")

    o := []byte{}

    for _,d := range s {
	i,err := strconv.Atoi(d)
	if err != nil {
		fmt.Printf("Err\n")
	}
	x := uint8(i)
	o = append(o,x)
    }

    cmd, err = exec.Command("/usr/local/bin/keystr",dst).Output()
    if err != nil {         
        fmt.Printf("err\n");           
    }                                  
    s = strings.Split(string(cmd),",")
    for _,d := range s {        
        i,err := strconv.Atoi(d)
        if err != nil {            
                fmt.Printf("Err\n")
        }                     	
	x := uint8(i)
	o = append(o,x)
    }                 


    
}

