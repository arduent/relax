package xyz.irides.irides

import android.content.Intent
import android.os.Bundle
import android.text.Editable
import android.text.TextWatcher
import android.util.Base64
import android.view.View
import android.widget.EditText
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.android.volley.Request
import com.android.volley.Response
import com.android.volley.VolleyError
import com.android.volley.toolbox.StringRequest
import com.android.volley.toolbox.Volley
import com.google.zxing.integration.android.IntentIntegrator
import com.google.zxing.integration.android.IntentResult
import kotlinx.android.synthetic.main.activity_main.*
import org.libsodium.jni.NaCl
import org.libsodium.jni.Sodium
import java.io.BufferedReader
import java.io.InputStreamReader


class MainActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        btnScan.setOnClickListener {
            run {
                IntentIntegrator(this@MainActivity).initiateScan();
            }
        }

        pwd.addTextChangedListener(object : TextWatcher {

            override fun afterTextChanged(s: Editable) {}

            override fun beforeTextChanged(
                s: CharSequence, start: Int,
                count: Int, after: Int
            ) {
            }

            override fun onTextChanged(s: CharSequence, start: Int, before: Int, count: Int) =
                if (s.isNotEmpty()) {
                    genKey.visibility = View.VISIBLE
                    loadKey.visibility = View.VISIBLE
                } else {
                    genKey.visibility = View.INVISIBLE
                    loadKey.visibility = View.INVISIBLE
                }
        })

        genKey.setOnClickListener {
            run {

                val intent = Intent()
                    .setType("*/*")
                    .setAction(Intent.ACTION_CREATE_DOCUMENT)
                    .addCategory(Intent.CATEGORY_OPENABLE)

                startActivityForResult(Intent.createChooser(intent, "Save file"), 111)

            }
        }

        loadKey.setOnClickListener {
            run {

                val intent = Intent()
                    .setType("*/*")
                    .setAction(Intent.ACTION_GET_CONTENT)

                startActivityForResult(Intent.createChooser(intent, "Load file"), 112)

            }
        }

    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {

        var result: IntentResult? = IntentIntegrator.parseActivityResult(
            requestCode,
            resultCode,
            data
        )

        if (requestCode == 112 && resultCode == RESULT_OK) {

            val textView = findViewById<TextView>(R.id.txtValue)
            textView.visibility = View.VISIBLE
            val skv = findViewById<TextView>(R.id.skv)
            val password = findViewById<EditText>(R.id.pwd)
            skv.text = "load"

            val bst = StringBuilder()
            val contentResolver = applicationContext.contentResolver
            data?.data?.let { it ->
                val xin = contentResolver.openInputStream(it)
                val reader = BufferedReader(InputStreamReader(xin))
                val lns = reader.readLines()
                lns.forEach {
                    bst.append(it)
                    bst.append("\n")
                }
                xin?.close()
            }
            val lns = bst.lines()
            val ciphertext = Base64.decode(lns[0], Base64.NO_WRAP)
            val nonce = Base64.decode(lns[1], Base64.NO_WRAP)
            val key = Base64.decode(lns[2], Base64.NO_WRAP)

            val sodium = NaCl.sodium()
            val hash = ByteArray(Sodium.crypto_secretbox_keybytes())
            val message = password.text.toString().toByteArray()

            Sodium.crypto_generichash(
                hash,
                Sodium.crypto_secretbox_keybytes(),
                message,
                message.size,
                key,
                key.size
            )

            val plaintext = ByteArray(ciphertext.size - Sodium.crypto_secretbox_macbytes())
            if (Sodium.crypto_secretbox_open_easy(
                    plaintext,
                    ciphertext,
                    ciphertext.size,
                    nonce,
                    hash
                )!=0)
            {
                textView.text = "Invalid Password"
            } else {
                val lsa = plaintext.toString(Charsets.UTF_8)
                val ls = lsa.lines()
                val pk = ls[1]
                val sk = ls[0]
                skv.text = sk
                val msg = pk.take(28) + "\n" + pk.takeLast(28)
                textView.text = msg
                btnScan.visibility = View.VISIBLE
            }


        } else if (requestCode == 111 && resultCode == RESULT_OK) {

            val textView = findViewById<TextView>(R.id.txtValue)
            textView.visibility = View.VISIBLE
            val skv = findViewById<TextView>(R.id.skv)
            skv.text = "load"
            val password = findViewById<EditText>(R.id.pwd)

            val queue = Volley.newRequestQueue(this)
            val url = "https://obitcoin.org/x-relax-genkey.php"
            val stringRequest = StringRequest(
                Request.Method.GET, url,
                { response ->
                    val ls = response.lines()
                    val sk = ls[0]
                    skv.text = sk
                    val pk = ls[1]

                    val sodium = NaCl.sodium()

                    val hash = ByteArray(Sodium.crypto_secretbox_keybytes())
                    val message = password.text.toString().toByteArray()
                    val key = ByteArray(Sodium.crypto_generichash_keybytes())
                    val nonce = ByteArray(Sodium.crypto_secretbox_noncebytes())

                    Sodium.randombytes(key, key.size)
                    Sodium.randombytes(nonce, nonce.size)

                    Sodium.crypto_generichash(
                        hash,
                        Sodium.crypto_secretbox_keybytes(),
                        message,
                        message.size,
                        key,
                        key.size
                    )

                    //store the random key but the encrypt key is actually the hash of the key and the password
                    val nres = response.toString()
                    val nresba = nres.toByteArray()
                    val sb = StringBuilder()
                    val ciphertext = ByteArray(nresba.size + Sodium.crypto_secretbox_macbytes())
                    Sodium.crypto_secretbox_easy(ciphertext, nresba, nresba.size, nonce, hash)
                    sb.append(Base64.encodeToString(ciphertext, Base64.NO_WRAP))
                    sb.append("\n")
                    sb.append(Base64.encodeToString(nonce, Base64.NO_WRAP))
                    sb.append("\n")
                    sb.append(Base64.encodeToString(key, Base64.NO_WRAP))
                    val outstr = sb.toString().toByteArray()
                    val contentResolver = applicationContext.contentResolver
                    data?.data?.let {
                        val out = contentResolver.openOutputStream(it)
                        out?.write(outstr)
                        out?.close()
                    }
                    val res =
                        sk.take(28) + "\n" + sk.takeLast(28) + "\n\n" + pk.take(28) + "\n" + pk.takeLast(
                            28
                        ) + "\n\nWrite this down. IMPORTANT!" + sb.toString()

                    //display this so the user can write it down
                    textView.text = res
                    skv.text = sk
                },
                { textView.text = "That didn't work!" })
            queue.add(stringRequest)
        } else {
            if (result != null) {
                if (result.contents != null) {
                    //HERE

                    val skv = findViewById<TextView>(R.id.skv)
                    val sk: String = skv.text.toString()
                    val trxcode: String = result.contents

                    val url = "https://obitcoin.org/x-relax-sign.php"

                    val stringRequest: StringRequest = object : StringRequest(
                        Method.POST,
                        url,
                        Response.Listener { response ->
                            Toast.makeText( this, response.toString(), Toast.LENGTH_LONG).show()
                        },
                        Response.ErrorListener {
                            error ->
                            Toast.makeText(this, error.toString(), Toast.LENGTH_LONG).show()
                        }) {
                        override fun getParams(): Map<String, String> {
                            val params: MutableMap<String, String> = HashMap()
                            params["sk"] = sk
                            params["trx"] = trxcode
                            return params
                        }
                    }
                    val requestQueue = Volley.newRequestQueue(this)
                    requestQueue.add(stringRequest)

                } else {
                    txtValue.text = "the scan failed"
                }
            } else {
                super.onActivityResult(requestCode, resultCode, data)
            }
        }
    }
}
