<?php

namespace tspl_printer\src;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use tspl_printer\src\PrinterConnectors\PrintConnector;

class TsplPrinter
{
    const DPI200 = 8;
    const DPI300 = 12;

    const MILIMETER = "mm";
    const DOT = "dot";
    const INCH = "";

    const LINE_BREAK = "\r\n";
    const SEPARATOR = ",";
    const SPACE = " ";

    //Configuration related
    const SIZE = "SIZE";
    const GAP = "GAP";
    const REFERENCE = "REFERENCE";
    const DIRECTION = "DIRECTION";
    const OFFSET = "OFFSET";
    const SHIFT = "SHIFT";
    const TEAR = "SET TEAR";
    const CODEPAGE = "CODEPAGE";
    const AUTODETECT = "AUTODETECT";

    //Action related command
    const TEXT = "TEXT";
    const BEEP = "BEEP";
    const BITMAP = "BITMAP";
    const PRINT = "PRINT";
    const BARCODE = "BARCODE";
    const PUTBMP = "PUTBMP";
    const BAR = "BAR";
    const REVERSE = "REVERSE";

    //Single word command
    const CLS = "CLS";
    const EOP = "EOP";
    const HOME = "HOME";
    const CUT = "CUT";
    const DEFAULT_UNIT = "";

    const STATUS =  0x1B213F;
    const NAME_PRINTER = 0x7E2154;

    //Barcode
    const BARCODE_128 = "128";
    const BARCODE_128M = "128M";
    const BARCODE_EAN128 = "EAN128";
    const BARCODE_25 = "25";
    const BARCODE_25C = "25C";
    const BARCODE_39 = "39";
    const BARCODE_39C = "39C";
    const BARCODE_93 = "93";
    const BARCODE_EAN13 = "EAN13";
    const BARCODE_EAN13_2 = "EAN13+2";
    const BARCODE_EAN13_5 = "EAN13+5";
    const BARCODE_EAN8 = "EAN8";
    const BARCODE_EAN8_2 = "EAN8+2";
    const BARCODE_EAN8_5 = "EAN8+5";
    const BARCODE_CODA = "CODA";
    const BARCODE_POST = "POST";
    const BARCODE_UPCA = "UPCA";
    const BARCODE_UPCA_2 = "UPCA+2";
    const BARCODE_UPA_5 = "UPA+5";
    const BARCODE_UPCE = "UPCE";
    const BARCODE_UPCE_2 = "UPCE+2";
    const BARCODE_UPE_5 = "UPE+5";
    const BARCODE_MSI = "MSI";
    const BARCODE_MSIC = "MSIC";
    const BARCODE_PLESSEY = "PLESSEY";
    const BARCODE_CPOST = "CPOST";
    const BARCODE_ITF14 = "ITF14";
    const BARCODE_EAN14 = "EAN14";
    const BARCODE_11 = "11";
    const BARCODE_TELEPEN = "TELEPEN";
    const BARCODE_TELEPENN = "TELEPENN";
    const BARCODE_PLANET = "PLANET";
    const BARCODE_CODE49 = "CODE49";
    const BARCODE_DPI = "DPI";
    const BARCODE_DPL = "DPL";
    const BARCODE_LOGMARS = "LOGMARS";

    protected $connector;

    private $defaultUnit;

    private $sizeWidth = 35;
    private $sizeHeight = 25;
    private $sizeUnit;

    private $gapDistance = "5";
    private $gapOffset;
    private $gapUnit;

    private $referenceX = 0;
    private $referenceY = 0;

    private $offset = 0;
    private $offsetUnit;

    private $shiftX;
    private $shiftY = 0;

    private $direction = 1;

    private $paper_length;
    private $gap_length;

    private $codepage;

    private $tear;
    private $eop;
    private $cut;
    private static $printerName;

    private $content = [];

    public function __construct(PrintConnector $connector)
    {
        $this->connector = $connector;
    }

    public function setDefaultUnit($defaultUnit) {
        $this->defaultUnit = $defaultUnit;
        return $this;
    }

    public function setSize($width, $height = null, $unit = null) {
        $this->sizeWidth = $width;
        $this->sizeHeight = $height;
        $this->sizeUnit = $unit;
        return $this;
    }

    public function setGap($distance, $offset, $unit = null)
    {
        $this->gapDistance = $distance;
        $this->gapOffset = $offset;
        $this->gapUnit = $unit;
        return $this;
    }

    public function setAutodetect($paper_length, $gap_length)
    {
        $this->paper_length = $paper_length;
        $this->gap_length = $gap_length;

        return $this;
    }

    public function setReference($x, $y) {
        $this->referenceX = $x;
        $this->referenceY = $y;
        return $this;
    }

    public function setDirection($direction) {
        $this->direction = $direction;
        return $this;
    }

    public function setOffset($offset, $unit = null){
        $this->offset = $offset;
        $this->offsetUnit = $unit;
        return $this;
    }

    public function setShift($y, $x = null){
        $this->shiftX = $x;
        $this->shiftY = $y;
        return $this;
    }

    public function setCodePage($code)
    {
        $this->codepage = $code;
        return $this;
    }

    public function setTear(int $tear)
    {
        $this->tear = $tear;
        return $this;
    }

    public function setEop()
    {
        $this->eop = 1;
        return $this;
    }

    public function setCut()
    {
        $this->cut = 1;
        return $this;
    }

    public function getSizeCommand()
    {
        $str = self::SIZE;
        $str .= self::SPACE;
        $str .= $this->sizeWidth;
        $str .= self::SPACE;
        $str .= $this->getUnit($this->sizeUnit);
        if(isset($this->sizeHeight)) {
            $str .= self::SEPARATOR;
            $str .= $this->sizeHeight;
            $str .= self::SPACE;
            $str .= $this->getUnit($this->sizeUnit);
        }
        return $str;
    }

    public function getGapCommand()
    {
        $str = self::GAP;
        $str .= self::SPACE;
        $str .= $this->gapDistance;
        $str .= self::SPACE;
        $str .= $this->getUnit($this->gapUnit);
        $str .= self::SEPARATOR;
        $str .= $this->gapOffset;
        $str .= self::SPACE;
        $str .= $this->getUnit($this->gapUnit);
        return $str;
    }

    public function getReferenceCommand()
    {
        $str = self::REFERENCE;
        $str .= self::SPACE;
        $str .= $this->referenceX;
        $str .= self::SEPARATOR;
        $str .= $this->referenceY;
        return $str;
    }

    public function getDirectionCommand()
    {
        $str = self::DIRECTION;
        $str .= self::SPACE;
        $str .= $this->direction;
        return $str;
    }

    public function getOffsetCommand()
    {
        $str = self::OFFSET;
        $str .= self::SPACE;
        $str .= $this->offset;
        $str .= self::SPACE;
        $str .= $this->getUnit($this->offsetUnit);
        return $str;
    }

    public function getShiftCommand()
    {
        $str = self::SHIFT;
        $str .= self::SPACE;
        if($this->shiftX) {
            $str .= $this->shiftX;
            $str .= self::SEPARATOR;
        }
        $str .= $this->shiftY;
        return $str;
    }

    public function getBitmapCommand($x, $y, $withBytes, $heightDots, $mode, $data)
    {
        $str = self::BITMAP;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= $withBytes;
        $str .= self::SEPARATOR;
        $str .= $heightDots;
        $str .= self::SEPARATOR;
        $str .= $mode;
        $str .= self::SEPARATOR;
        $str .= $data;
        return $str;
    }

    public function getPrintCommand($set, $copy = null)
    {
        $str = self::PRINT;
        $str .= self::SPACE;
        $str .= $set;
        if(isset($copy)) {
            $str .= self::SEPARATOR;
            $str .= $copy;
        }
        return $str;
    }

    private function getAutodetect()
    {
        $str = self::AUTODETECT;
        $str .= self::SPACE;
        $str .= $this->paper_length;
        $str .= self::SEPARATOR;
        $str .= $this->gap_length;

        return $str;
    }

    private function getTear()
    {
        $tear = self::TEAR;
        $tear .= self::SPACE;
        $this->tear ? $tear .= "ON" : $tear .= "OFF";

        return $tear;
    }

    private function getHome()
    {
        return self::HOME;
    }

    private function getEop()
    {
        return self::EOP;
    }

    private function getCut()
    {
        return self::CUT;
    }

    private function getCodePage()
    {
        $code = self::CODEPAGE;
        $code .= self::SPACE;
        $code .= $this->codepage;
        return $code;
    }

    /**
     * Оформление шапки
     * @return array
     */
    private function getHeaderCommands()
    {
        $header = [];
        if(!empty($this->codepage)){
            array_push($header, $this->getCodePage());
        }
        array_push($header, $this->getSizeCommand());
        array_push($header, $this->getGapCommand());
        if(!empty($this->paper_length)){
            array_push($header, $this->getAutodetect());
        }
        array_push($header, $this->getReferenceCommand());
        array_push($header, $this->getDirectionCommand());
//        array_push($header, $this->getShiftCommand());
        if (!empty($this->tear)){
            array_push($header, $this->getTear());
        }
        array_push($header, self::CLS);

        return $header;
    }

    /**
     * Оформление подвала
     * @return array
     */
    private function getFooterCommands()
    {
        $footer = [];
        array_push($footer, $this->getPrintCommand(1));
        if(!empty($this->cut)){
            array_push($footer, $this->getCut());
        }
        if(!empty($this->eop)){
            array_push($footer, $this->getEop());
        }

        return $footer;
    }

    /**
     * Считывание статуса принтера
     * @return string|null
     */
    private function getStatus()
    {
        $status = null;
        $this->connector->write(pack('N',self::STATUS));
        $result = $this->connector->read(1);
        switch ($result){
            case 0x00:
                $status = 'Нормально';
                break;
            case 0x01:
                $status = 'Открыта крышка';
                break;
            case 0x02:
                $status = 'Застряла бумага';
                break;
            case 0x03:
                $status = 'Застряла бумага, открыта крышка';
                break;
            case 0x04:
                $status = 'Нет бумаги';
                break;
            case 0x05:
                $status = 'Нет бумаги, открыта крышка';
                break;
            case 0x08:
                $status = 'Нет ленты';
                break;
            case 0x09:
                $status = 'Нет ленты, открыта крышка';
                break;
            case 0x0A:
                $status = 'Нет ленты, застряла бумага';
                break;
            case 0x0B:
                $status = 'Нет ленты, застряла бумага и открыта крышка';
                break;
            case 0x0C:
                $status = 'Нет ленты и нет бумаги';
                break;
            case 0x0D:
                $status = 'Нет ленты, нет бумаги и открыта крышка';
                break;
            case 0x10:
                $status = 'Пауза';
                break;
            case 0x20:
                $status = 'Идет печать';
                break;
            case 0x80:
                $status = 'Другая ошибка';
                break;
        }
        return $status;
    }

    /**
     * Считывание модели принтера
     * @return string|false
     */
    public function getNamePrinter()
    {
        $this->connector->write(pack('N',self::NAME_PRINTER));
        $name = $this->connector->read(10);
        return $name;
    }

    public function beep()
    {
        array_push($this->content, self::BEEP);
    }

    /**
     * @param string $text
     * @param $code_type
     * @param int $x
     * @param int $y
     * @param int $height Высота штрих-кода
     * @param int $human_readable Расшифрока штрих-кода
     * @param int $rotation Поворот
     * @param int $narrow Минимальная ширина линии
     * @param int $alignment
     */
    public function barcode(string $text, $code_type, int $height, $x = 0, int $y = 0, int $human_readable = 0, int $rotation = 0, int $narrow = 2, int $alignment = null)
    {
        $wide = 1;
        $len = strlen($text);
        switch ($code_type){
            case self::BARCODE_128:
                self::validateInteger($len,1,48, __FUNCTION__, "128 barcode content length");
                self::validateTextAscii($text,__FUNCTION__,"128 barcode content");
                $wide = $narrow * 1;
                break;
//            case self::BARCODE_128M:
//                self::validateInteger($len,1,255, __FUNCTION__, "128M barcode content length");
//                $wide = $narrow * 1;
//                break;
            case self::BARCODE_EAN128:
                self::validateInteger($len,1,48, __FUNCTION__, "EAN128 barcode content length");
                self::validateTextAscii($text, __FUNCTION__,"EAN128 barcode content");
                $wide = $narrow * 1;
                break;
//            case self::BARCODE_25:
//                self::validateInteger($len,1,255, __FUNCTION__, "25 barcode content length");
//                $wide = $narrow * 3;
//                break;
//            case self::BARCODE_25C:
//                self::validateInteger($len,1,255, __FUNCTION__, "25C barcode content length");
//                $wide = $narrow * 3;
//                break;
//            case self::BARCODE_39:
//                self::validateInteger($len,1,255, __FUNCTION__, "39 barcode content length");
//                $wide = $narrow * 3;
//                break;
//            case self::BARCODE_39C:
//                self::validateInteger($len,1,255, __FUNCTION__, "39C barcode content length");
//                $wide = $narrow * 3;
//                break;
//            case self::BARCODE_93:
//                self::validateInteger($len,1,255, __FUNCTION__, "93 barcode content length");
//                $wide = $narrow * 3;
//                break;
            case self::BARCODE_EAN13:
                self::validateInteger($len,1,12, __FUNCTION__, "EAN13 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,12}$/", "EAN13 barcode content");
                $wide = $narrow * 1;
                break;
            case self::BARCODE_EAN13_2:
                self::validateInteger($len,1,14, __FUNCTION__, "EAN13+2 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,14}$/", "EAN13+2 barcode content");
                $wide = $narrow * 1;
                break;
            case self::BARCODE_EAN13_5:
                self::validateInteger($len,1,17, __FUNCTION__, "EAN13+5 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,17}$/", "EAN13+5 barcode content");
                $wide = $narrow * 1;
                break;
            case self::BARCODE_EAN8:
                self::validateInteger($len,1,7, __FUNCTION__, "EAN8 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,7}$/", "EAN8 barcode content");
                $wide = $narrow * 1;
                break;
            case self::BARCODE_EAN8_2:
                self::validateInteger($len,1,9, __FUNCTION__, "EAN8+2 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,9}$/", "EAN8+2 barcode content");
                $wide = $narrow * 1;
                break;
            case self::BARCODE_EAN8_5:
                self::validateInteger($len,1,12, __FUNCTION__, "EAN8+5 barcode content length");
                self::validateText($text,__FUNCTION__,"/^[0-9]{1,12}$/", "EAN8+5 barcode content");
                $wide = $narrow * 1;
                break;
//            case self::BARCODE_CODA:
//                self::validateInteger($len,1,255, __FUNCTION__, "CODA barcode content length");
//                $wide = $narrow * 3;
//                break;
//            case self::BARCODE_POST:
//                self::validateInteger($len,1,255, __FUNCTION__, "POST barcode content length");
//                $wide = $narrow * 1;
//                break;
        }

        $str = self::BARCODE;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= '"';
        $str .= $code_type;
        $str .= '"';
        $str .= self::SEPARATOR;
        $str .= $height;
        $str .= self::SEPARATOR;
        $str .= $human_readable;
        $str .= self::SEPARATOR;
        $str .= $rotation;
        $str .= self::SEPARATOR;
        $str .= $narrow;
        $str .= self::SEPARATOR;
        $str .= $wide;
        $str .= self::SEPARATOR;
        if (!empty($alignment)){
            $str .= $alignment;
            $str .= self::SEPARATOR;
        }
        $str .= '"';
        $str .= $text;
        $str .= '"';

        array_push($this->content, $str);
    }

    /**
     * Проверка длины текста
     * @param $len
     * @param int $min
     * @param int $max
     * @param string $source
     * @param string $argument
     */
    private static function validateInteger(int $len, int $min, int $max, string $source, string $argument = "Argument")
    {
        if (!is_integer($len)) {
            throw new InvalidArgumentException("$argument given to $source must be a number, but '$len' was given.");
        }
    }

    /**
     * @param string $text
     * @param string $source
     * @param string $regex
     * @param string $argument
     */
    private static function validateText(string $text, string $source, string $regex, string $argument = "Argument")
    {
        if (preg_match($regex, $text) === 0) {
            throw new InvalidArgumentException("$argument given to $source is invalid. It should match regex '$regex', but '$text' was given.");
        }
    }

    /**
     * @param string $text
     * @param string $source
     * @param string $argument
     */
    private static function validateTextAscii(string $text, string $source, string $argument = "Argument")
    {
        if(mb_detect_encoding($text, 'ASCII', 1)){
            throw new InvalidArgumentException("$argument given to $source is invalid. Данный текст '$text' не является ASCII.");
        }
    }

    /**
     * Данные для формирования текста
     * @param $text
     * @param int $x
     * @param int $y
     * @param int $font
     * @param int $rotation
     * @param int $xMultiplication
     * @param int $yMultiplication
     * @param null $alignment
     */
    public function text($text, $x = 0, $y = 0, $font = 0, $rotation = 0, $xMultiplication = 0, $yMultiplication = 0, $alignment = null)
    {
        $str = self::TEXT;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= '"';
        $str .= $font;
        $str .= '"';
        $str .= self::SEPARATOR;
        $str .= $rotation;
        $str .= self::SEPARATOR;
        $str .= $xMultiplication;
        $str .= self::SEPARATOR;
        $str .= $yMultiplication;
        if(isset($alignment)){
            $str .= self::SEPARATOR;
            $str .= $alignment;
        }
        $str .= self::SEPARATOR;
        $str .= '"';
        $str .= $text;
        $str .= '"';

        array_push($this->content, $str);
    }

    /**
     * Создании линии
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     */
    public function bar(int $x, int $y, int $width = 10, int $height = 2)
    {
        $str = self::BAR;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= $width;
        $str .= self::SEPARATOR;
        $str .= $height;

        array_push($this->content, $str);
    }

    /**
     * Реверс цвета заданной области
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     */
    public function reverse(int $x, int $y, int $width = 10, int $height = 10)
    {
        $str = self::REVERSE;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= $width;
        $str .= self::SEPARATOR;
        $str .= $height;

        array_push($this->content, $str);
    }

    public function imageBmp($image, int $x = 0, int $y = 0, int $bpp = null, int $contrast = null)
    {
        switch (self::$printerName){
            case 'TDP-643 Plus':
            case 'TTP-243':
            case 'TTP-342':
            case 'TTP-244ME':
            case 'TTP-342M':
            case 'TTP-248M':
                throw new BadMethodCallException('Данный метод "'. self::PUTBMP .'"" на данном принтере '. self::$printerName .' не поддерживается');
        }
        $str = self::PUTBMP;
        $str .= self::SPACE;
        $str .= $x;
        $str .= self::SEPARATOR;
        $str .= $y;
        $str .= self::SEPARATOR;
        $str .= '"';
        $str .= $image;
        $str .= '"';
        if (!empty($bpp)){
            if ($bpp !== 1 && $bpp !== 8){
                throw new InvalidArgumentException('Значение bpp = '. $bpp . ' не совпадает с протоколом TSPL(либо 1, либо 8)');
            }
            $str .= self::SEPARATOR;
            $str .= $bpp;
        }
        if (!empty($contrast)){
            if ($contrast < 60 || $contrast > 100){
                throw new InvalidArgumentException('Значение contrast = '. $contrast . ' за границей диапазона 60...100');
            }
            $str .= self::SEPARATOR;
            $str .= $contrast;
        }

        array_push($this->content, $str);
    }

    protected function getUnit($unit)
    {
        return isset($unit) ? $unit : $this->defaultUnit ? $this->defaultUnit : self::DEFAULT_UNIT;
    }

    /**
     * Отправляет данные на принтер
     */
    public function sendCommands()
    {
//        if (empty(self::$printerName)){
//            self::$printerName = $this->getNamePrinter();
//        }
        $result = array_merge($this->getHeaderCommands(), $this->content, $this->getFooterCommands());
        $commandString = implode(self::LINE_BREAK, $result).self::LINE_BREAK;
        $this->connector->write($commandString);
    }

    /**
     * Закрывает соединение
     */
    public function close()
    {
        $this->connector->finalize();
    }
}