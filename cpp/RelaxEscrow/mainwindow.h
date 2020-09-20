#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QMainWindow>
#include <QSystemTrayIcon>
#include <KWallet>

QT_BEGIN_NAMESPACE
namespace Ui { class MainWindow; }
QT_END_NAMESPACE

class MainWindow : public QMainWindow
{
    Q_OBJECT

public:
    MainWindow(QWidget *parent = nullptr);
    ~MainWindow();

private slots:
    void onShowHide();
    void onShowHide(QSystemTrayIcon::ActivationReason reason);
    void onExit();
    void walletOpened(bool ok);
    void mission();

private:
    Ui::MainWindow *ui;
    QString randstr(void);
    KWallet::Wallet *m_wallet;
    QString sk;
    void createTrayIcon();

    QSystemTrayIcon *m_tray_icon;

};
#endif // MAINWINDOW_H
