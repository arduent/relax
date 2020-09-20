#include <iostream>
#include <fstream>
#include <cstdio>
#include <string>
#include <unistd.h>
#include "fcgio.h"
#include "sodium.h"
#include "qrencode.h"
#include "png.h"

using namespace std;

static const size_t ZOOM_SIZE = 10;
#define INCHES_PER_METER (100.0/2.54)

static int casesensitive = 0;
static int eightbit = 0;
static int version = 0;
static int size = 5;
static int margin = 3;
static int dpi = 72;
static int structured = 0;
static int rle = 0;
static int micro = 0;
static QRecLevel level = QR_ECLEVEL_L;
static QRencodeMode hint = QR_MODE_8;
static unsigned int fg_color[4] = {0, 0, 0, 255};
static unsigned int bg_color[4] = {255, 255, 255, 255};

std::string writePNG(QRcode *qrcode)
{
    static FILE *fp;
    png_structp png_ptr;
    png_infop info_ptr;
    png_colorp palette;
    png_byte alpha_values[2];
    unsigned char *row, *p, *q;
    int x, y, xx, yy, bit;
    int realwidth;

    std::string fn = std::tmpnam(nullptr);

    realwidth = (qrcode->width + margin * 2) * size;
    row = (unsigned char *)malloc((realwidth + 7) / 8);
    if(row == NULL) {
        fprintf(stderr, "Failed to allocate memory.\n");
        exit(EXIT_FAILURE);
    }

    fp = fopen(fn.c_str(),"wb");

    png_ptr = png_create_write_struct(PNG_LIBPNG_VER_STRING, NULL, NULL, NULL);
    if(png_ptr == NULL) {
        fprintf(stderr, "Failed to initialize PNG writer.\n");
        exit(EXIT_FAILURE);
    }

    info_ptr = png_create_info_struct(png_ptr);
    if(info_ptr == NULL) {
        fprintf(stderr, "Failed to initialize PNG write.\n");
        exit(EXIT_FAILURE);
    }

    if(setjmp(png_jmpbuf(png_ptr))) {
        png_destroy_write_struct(&png_ptr, &info_ptr);
        fprintf(stderr, "Failed to write PNG image.\n");
        exit(EXIT_FAILURE);
    }

    palette = (png_colorp) malloc(sizeof(png_color) * 2);
    if(palette == NULL) {
        fprintf(stderr, "Failed to allocate memory.\n");
        exit(EXIT_FAILURE);
    }
    palette[0].red   = fg_color[0];
    palette[0].green = fg_color[1];
    palette[0].blue  = fg_color[2];
    palette[1].red   = bg_color[0];
    palette[1].green = bg_color[1];
    palette[1].blue  = bg_color[2];
    alpha_values[0] = fg_color[3];
    alpha_values[1] = bg_color[3];
    png_set_PLTE(png_ptr, info_ptr, palette, 2);
    png_set_tRNS(png_ptr, info_ptr, alpha_values, 2, NULL);

    png_init_io(png_ptr, fp);
    png_set_IHDR(png_ptr, info_ptr,
        realwidth, realwidth,
        1,
        PNG_COLOR_TYPE_PALETTE,
        PNG_INTERLACE_NONE,
        PNG_COMPRESSION_TYPE_DEFAULT,
        PNG_FILTER_TYPE_DEFAULT);
png_set_pHYs(png_ptr, info_ptr,
        dpi * INCHES_PER_METER,
        dpi * INCHES_PER_METER,
        PNG_RESOLUTION_METER);
png_write_info(png_ptr, info_ptr);

/* top margin */
memset(row, 0xff, (realwidth + 7) / 8);
for(y=0; y<margin * size; y++) {
    png_write_row(png_ptr, row);
}

/* data */
p = qrcode->data;
for(y=0; y<qrcode->width; y++) {
    bit = 7;
    memset(row, 0xff, (realwidth + 7) / 8);
    q = row;
    q += margin * size / 8;
    bit = 7 - (margin * size % 8);
    for(x=0; x<qrcode->width; x++) {
        for(xx=0; xx<size; xx++) {
            *q ^= (*p & 1) << bit;
            bit--;
            if(bit < 0) {
                q++;
                bit = 7;
            }
        }
        p++;
    }
    for(yy=0; yy<size; yy++) {
        png_write_row(png_ptr, row);
    }
}
/* bottom margin */
memset(row, 0xff, (realwidth + 7) / 8);
for(y=0; y<margin * size; y++) {
    png_write_row(png_ptr, row);
}

png_write_end(png_ptr, info_ptr);
png_destroy_write_struct(&png_ptr, &info_ptr);

fclose(fp);
free(row);
free(palette);

return fn;
}


int main(void) {
	streambuf * cin_streambuf  = cin.rdbuf();
	streambuf * cout_streambuf = cout.rdbuf();
	streambuf * cerr_streambuf = cerr.rdbuf();

	if (sodium_init() == -1) {
		return 1;
	}

        size_t pk_bin_len = crypto_sign_PUBLICKEYBYTES;
        const size_t sk_bin_len = crypto_sign_SECRETKEYBYTES;
        size_t pk_base64_max_len =
                        sodium_base64_encoded_len(pk_bin_len,
                                sodium_base64_VARIANT_URLSAFE);
        const size_t sk_base64_max_len =
                        sodium_base64_encoded_len(sk_bin_len,
                                sodium_base64_VARIANT_URLSAFE);

        char * pk_base64_str = (char *)sodium_malloc(pk_base64_max_len);
        char * encoded_pk = (char *)sodium_malloc(pk_base64_max_len);
	char * sk_base64_str = (char *)sodium_malloc(sk_base64_max_len);
	char * encoded_sk = (char *)sodium_malloc(sk_base64_max_len);

	FCGX_Request request;
	FCGX_Init();
	FCGX_InitRequest(&request, 0, 0);

	while (FCGX_Accept_r(&request) == 0) {
		fcgi_streambuf cin_fcgi_streambuf(request.in);
		fcgi_streambuf cout_fcgi_streambuf(request.out);
		fcgi_streambuf cerr_fcgi_streambuf(request.err);

		cin.rdbuf(&cin_fcgi_streambuf);
		cout.rdbuf(&cout_fcgi_streambuf);
		cerr.rdbuf(&cerr_fcgi_streambuf);

		unsigned char pk[crypto_sign_PUBLICKEYBYTES]; 
		unsigned char sk[crypto_sign_SECRETKEYBYTES];

                crypto_sign_keypair(pk, sk);
                encoded_pk = sodium_bin2base64(
                                        pk_base64_str,
                                        pk_base64_max_len,
                                        pk,
                                        pk_bin_len,
                                        sodium_base64_VARIANT_URLSAFE);

                encoded_sk = sodium_bin2base64(
                                        sk_base64_str,
                                        sk_base64_max_len,
                                        sk,
                                        sk_bin_len,
                                        sodium_base64_VARIANT_URLSAFE);


		if (encoded_pk == NULL)
		{
			//crash and burn
		} else {
			char dout[1024];
			sprintf(dout,"%s\n%s",sk_base64_str,pk_base64_str);
			QRcode *qrcode = QRcode_encodeString(dout,0, QR_ECLEVEL_L, QR_MODE_8, 1);
			cout << "Content-type: image/png\n\n";
			std::string of = writePNG(qrcode);
			std::ifstream f(of);
			std::cout << f.rdbuf();
			unlink(of.c_str());
			QRcode_free(qrcode);

		}

    }

    // restore stdio streambufs
    cin.rdbuf(cin_streambuf);
    cout.rdbuf(cout_streambuf);
    cerr.rdbuf(cerr_streambuf);

    return 0;
}

