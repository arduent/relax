#include "mainwindow.h"
#include "ui_mainwindow.h"
#include <string.h>
#include <string>
#include <QSystemTrayIcon>
#include <QIcon>
#include <QAction>
#include <QTimer>
#include <KWallet>
#include <QInputDialog>
#include <cryptopp/cryptlib.h>
#include <cryptopp/aes.h>
#include <cryptopp/hex.h>
#include <cryptopp/ccm.h>
#include <cryptopp/sha.h>
#include <cryptopp/osrng.h>
#include <cryptopp/modes.h>
#include <cryptopp/filters.h>
#include <QDebug>
#include <QMessageBox>
#include <QDateTime>

MainWindow::MainWindow(QWidget *parent)
    : QMainWindow(parent)
    , ui(new Ui::MainWindow)
{
    ui->setupUi(this);

    m_wallet = KWallet::Wallet::openWallet(KWallet::Wallet::NetworkWallet(), winId(), KWallet::Wallet::Asynchronous);
    createTrayIcon();
    QTimer::singleShot(250,this,SLOT(hide()));
    connect(m_wallet, &KWallet::Wallet::walletOpened, this, &MainWindow::walletOpened);
}

MainWindow::~MainWindow()
{
    delete ui;

}

QString MainWindow::randstr(void)
{
    QString ret;
    int d = 256+rand()%119;
    for (int i=0;i<(d);i++)
    {
        if ((rand() % 100)>50)
        {
            char cch = 'A' + rand()%26;
            ret += cch;
        } else {
            ret += QString::number(rand()%10);
        }
    }
    return (ret);
}

void MainWindow::createTrayIcon()
{
    m_tray_icon = new QSystemTrayIcon(QIcon("22solar.png"), this);

    connect( m_tray_icon, SIGNAL(activated(QSystemTrayIcon::ActivationReason)), this, SLOT(onShowHide(QSystemTrayIcon::ActivationReason)) );

    QAction *quit_action = new QAction( "Exit", m_tray_icon );
    connect( quit_action, SIGNAL(triggered()), this, SLOT(onExit()) );

    QAction *hide_action = new QAction( "Show/Hide", m_tray_icon );
    connect( hide_action, SIGNAL(triggered()), this, SLOT(onShowHide()) );

    QMenu *tray_icon_menu = new QMenu;
    tray_icon_menu->addAction( hide_action );
    tray_icon_menu->addAction( quit_action );

    m_tray_icon->setContextMenu( tray_icon_menu );

    m_tray_icon->show();

}

void MainWindow::onShowHide()
{
    if( isVisible() )
    {
        hide();
    }
    else
    {
        show();
        raise();
        setFocus();
    }

}
void MainWindow::onShowHide(QSystemTrayIcon::ActivationReason reason)
{
    if( reason )
    {
        if( reason != QSystemTrayIcon::DoubleClick )
        return;
    }

    if( isVisible() )
    {
        hide();
    }
    else
    {
        show();
        raise();
        setFocus();
    }
}

void MainWindow::walletOpened(bool ok)
{
    if (ok &&
        (m_wallet->hasFolder("RelaxEscrow") ||
        m_wallet->createFolder("RelaxEscrow")) &&
        m_wallet->setFolder("RelaxEscrow")) {
        ui->msgLog->setPlainText("Wallet Loaded.");

        QByteArray be;
        bool decrypted = false;
        sk = "";
        if (m_wallet->hasEntry("sk"))
        {
            m_wallet->readEntry("sk",be);
            if (!be.isEmpty())
            {
                if (m_wallet->hasEntry("iv"))
                {
                    bool dok;
                    QString text = QInputDialog::getText(this, "Password For Decryption", "Enter the password to decrypt the Stellar Secret Key", QLineEdit::Password, "", &dok);
                    if (dok && !text.isEmpty())
                    {

                        std::string encoded = QString(be).toStdString();
                        std::string decoded;

                        CryptoPP::HexDecoder decoder;
                        decoder.Put((CryptoPP::byte*)encoded.data(), encoded.size());

                        CryptoPP::word64 size = decoder.MaxRetrievable();
                        if(size && size <= SIZE_MAX)
                        {
                            decoded.resize(size);
                            decoder.Get((CryptoPP::byte*)&decoded[0], decoded.size());
                        }

                        QByteArray ivbe;
                        m_wallet->readEntry("iv",ivbe);
                        if (!ivbe.isEmpty())
                        {
                            std::string iv_encoded = QString(ivbe).toStdString();
                            std::string iv_decoded;


                            CryptoPP::HexDecoder iv_decoder;
                            iv_decoder.Put((CryptoPP::byte*)iv_encoded.data(), iv_encoded.size());

                            CryptoPP::word64 iv_size = iv_decoder.MaxRetrievable();
                            if(iv_size && iv_size <= SIZE_MAX)
                            {
                                iv_decoded.resize(iv_size);
                                iv_decoder.Get((CryptoPP::byte*)&iv_decoded[0], iv_decoded.size());
                            }


                            const unsigned char *sequence = NULL;
                            sequence = (unsigned char*)qstrdup(text.toUtf8().constData());

                            CryptoPP::byte digest[CryptoPP::SHA1::DIGESTSIZE];
                            CryptoPP::SHA1().CalculateDigest(digest, sequence, strlen((char*)sequence));

                            CryptoPP::byte key[CryptoPP::AES::DEFAULT_KEYLENGTH];
                            std::memcpy(key,digest,sizeof(key));

                            CryptoPP::byte iv[CryptoPP::AES::BLOCKSIZE];
                            std::copy(iv_decoded.begin(), iv_decoded.end(), iv);

                            std::string plaintext;

                    try {

                            CryptoPP::CBC_Mode<CryptoPP::AES>::Decryption d;
                            d.SetKeyWithIV(key, sizeof(key), iv);
                            CryptoPP::StringSource s(decoded, true,
                            new CryptoPP::StreamTransformationFilter(d,
                            new CryptoPP::StringSink(plaintext)
                            ));


                            CryptoPP::StreamTransformationFilter filter(d);
                            filter.Put((const CryptoPP::byte*)decoded.data(), decoded.size());
                            filter.MessageEnd();

                            const size_t ret = filter.MaxRetrievable();
                            plaintext.resize(ret);
                            filter.Get((CryptoPP::byte*)plaintext.data(), plaintext.size());

                            }
                            catch(const CryptoPP::Exception& e)
                            {
                                QMessageBox msgBox;
                                msgBox.setText(e.what());
                                msgBox.exec();
                                exit(1);
                            }

                            QString tsk = QString::fromStdString(plaintext);

                            sk = tsk.right(56);
                            if (sk.left(1)=="S")
                            {
                                decrypted = true;
                            } else {
                                QMessageBox msgBox;
                                msgBox.setText("Could not decrypt secret key. Check password.");
                                msgBox.exec();
                                exit(1);
                            }
                            QTimer::singleShot(600000,this,SLOT(mission()));

                        }

                    }
                }
            }
        }

        if (sk.isEmpty())
        {
            bool dok;
            QString text = QInputDialog::getText(this, "Stellar Secret Key", "Enter the Stellar Secret Key for Escrow Account", QLineEdit::Normal, "", &dok);
            if (dok && !text.isEmpty())
            {
                sk = randstr() + text;
                text = QInputDialog::getText(this, "Password For Encryption", "Enter a password to encrypt this Stellar Secret Key", QLineEdit::Password, "", &dok);
                if (dok && !text.isEmpty())
                {
                    CryptoPP::AutoSeededRandomPool rnd;
                    CryptoPP::byte digest[CryptoPP::SHA1::DIGESTSIZE];
                    const unsigned char *sequence = NULL;
                    sequence = (unsigned char*)qstrdup(text.toUtf8().constData());

                    CryptoPP::SHA1().CalculateDigest(digest, sequence, strlen((char*)sequence));
                    CryptoPP::byte iv[CryptoPP::AES::BLOCKSIZE];
                    rnd.GenerateBlock(iv, sizeof(iv));

                    CryptoPP::byte key[CryptoPP::AES::DEFAULT_KEYLENGTH];
                    std::memcpy(key,digest,CryptoPP::AES::DEFAULT_KEYLENGTH);

                    std::string plaintext = sk.toUtf8().constData();
                    std::string ciphertext;

                    CryptoPP::CBC_Mode<CryptoPP::AES>::Encryption e;
                    e.SetKeyWithIV(key, sizeof(key), iv);
                    CryptoPP::StringSource s(plaintext, true,
                        new CryptoPP::StreamTransformationFilter(e,
                        new CryptoPP::StringSink(ciphertext)
                        )
                    );
                    CryptoPP::StreamTransformationFilter filter(e);
                    filter.Put((const CryptoPP::byte*)plaintext.data(), plaintext.size());
                    filter.MessageEnd();
                    const size_t ret = filter.MaxRetrievable();
                    ciphertext.resize(ret);
                    filter.Get((CryptoPP::byte*)ciphertext.data(), ciphertext.size());

                    std::string encoded;
                    encoded.clear();
                    CryptoPP::StringSource(ciphertext, true,
                    new CryptoPP::HexEncoder(
                    new CryptoPP::StringSink(encoded)
                    ));
                    QByteArray skba(encoded.c_str(), encoded.length());
                    m_wallet->writeEntry("sk",skba);

                    encoded.clear();
                    CryptoPP::StringSource(iv, sizeof(iv), true,
                    new CryptoPP::HexEncoder(
                    new CryptoPP::StringSink(encoded)
                    ));
                    QByteArray ivba(encoded.c_str(), encoded.length());
                    m_wallet->writeEntry("iv",ivba);

                }
            }
        }

    } else {
        ui->msgLog->setPlainText("Error Loading Wallet.");
    }
}

void MainWindow::mission()
{
    QDateTime now = QDateTime::currentDateTime();
    ui->msgLog->appendPlainText("\n"+QString::number(now.toSecsSinceEpoch()) + "\t"+sk);
    QTimer::singleShot(600000,this,SLOT(mission()));
}

void MainWindow::onExit()
{
        QApplication::exit();
}

