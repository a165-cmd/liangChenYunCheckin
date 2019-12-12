<?php
/**
 * Created by IntelliJ IDEA.
 * User: hugh.li
 * Date: 2019/12/12
 * Time: 15:48
 */

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Process\Process;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$email = $argv[1];
$password = $argv[2];

/** 启动 selenium */
$process = new Process(['java', '-jar', '/usr/bin/selenium.jar', '-port', '4444']);
$process->start();
sleep(10);
if (!$process->isRunning()) {
    echo $process->getErrorOutput(), PHP_EOL;
    throw new \Exception();
}
echo "selenium 启动成功", PHP_EOL;

$isOk = false;

$url = 'https://call-3u8633.com/';
$host = 'http://localhost:4444/wd/hub';

/** 设置代理调试的时候使用 */
//$capabilities->setCapability(WebDriverCapabilityType::PROXY, ['proxyType' => 'system', 'httpProxy' => 'localhost:8888']);

$options = new ChromeOptions();
$options->addArguments(['--headless', '--no-sandbox']);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = RemoteWebDriver::create($host, $capabilities, (120 * 1000), (120 * 1000));

$driver->get($url);

/** 浏览器最大化 */
$driver->manage()->window()->maximize();

/** 等待页面跳转成功 */
$class = WebDriverBy::className('content');
$condition = WebDriverExpectedCondition::visibilityOfElementLocated($class);
$driver->wait(10)->until($condition);

/** 跳登入 */
$buttonElement = $driver->findElement(WebDriverBy::id('wrapper'))
    ->findElement(WebDriverBy::linkText('登录'));
$driver->executeScript("arguments[0].click();", [$buttonElement]);


/** 邮箱和密码的输入框 */
$emailInput = WebDriverBy::cssSelector('input[name="Email"]');
$passwordInput = WebDriverBy::cssSelector('input[name="Password"]');

/** 等待 邮箱输入框 被渲染出来 */
$driver->wait(30)->until(
    WebDriverExpectedCondition::visibilityOfElementLocated($emailInput)
);


/** 输入 邮箱和密码 */
$driver->findElement($emailInput)->sendKeys($email);
$driver->findElement($passwordInput)->sendKeys($password);

/** 点击登入 */
$loginButton = WebDriverBy::cssSelector('button[id="login"]');
$loginButtonElement = $driver->findElement($loginButton);
$driver->executeScript("arguments[0].click();", [$loginButtonElement]);


/** 等待首页被渲染出来 */
sleep(20);

/** 确认按钮 */
try {
    $resultOk = WebDriverBy::cssSelector('button[id="result_ok"]');
    $resultOkElement = $driver->findElement($resultOk);
    $driver->executeScript("arguments[0].click();", [$resultOkElement]);
} catch (NoSuchElementException $exception) {
}

/** 点击签到 */
try {
    $checkinButton = WebDriverBy::cssSelector('button[id="checkin"]');
    $checkinButtonElement = $driver->findElement($checkinButton);
    $driver->executeScript("arguments[0].click();", [$checkinButtonElement]);
} catch (NoSuchElementException $exception) {
}

/** 等待完成信息 */
if (!$isOk) {
    try {
        sleep(10);
        $checkinMessage = WebDriverBy::cssSelector('p[id="checkin-msg"]');
        echo $driver->findElement($loginButton)->getText(), PHP_EOL;

        $isOk = true;
    } catch (NoSuchElementException $exception) {
    }
}

if (!$isOk) {
    try {
        /** 已经签到 */
        $message = WebDriverBy::cssSelector('a.btn.btn-brand.disabled.btn-flat');
        $text = $driver->findElement($message)->getText();
        if (0 >= strlen(trim($text))) {
            throw new \Exception();
        }

        echo $text, PHP_EOL;
        $isOk = true;
    } catch (NoSuchElementException $exception) {
    }
}

$driver->close();
$driver->quit();

if (!$isOk) {
    throw new \Exception();
}
