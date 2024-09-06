<?php


namespace PayFast;


use PayFast\Exceptions\InvalidRequestException;

class Validate
{

    /**
     * Validate Date
     * validateDate('14:50', 'H:i')
     * @param $date
     * @param string $format
     * @param string $paramName
     * @return mixed
     * @throws InvalidRequestException
     */
    public static function validateDate($date, $format = 'Y-m-d H:i:s', $paramName = "date")
    {
        $createDate = date_create($date);
        if($createDate == '' || date_format($createDate, $format) !== $date){
            throw new InvalidRequestException('Invalid date format for "'.$paramName.'"', 400);
        }
        return $date;
    }

    /**
     * Validate integers
     * @param $value
     * @param $key
     * @return void
     * @throws InvalidRequestException
     */
    private static function validateInt($value, $key): void
    {
        if(!is_numeric($value)) {
            throw new InvalidRequestException('Invalid format for "'.$key.'"', 400);
        }
    }

    /**
     * Validate account type
     * @param $value
     * @param $key
     * @throws InvalidRequestException
     */
    private static function validateAccType($value, $key): void
    {
        $validAccTypes = ['current', 'savings'];
        if(!in_array($value, $validAccTypes, true)) {
            throw new InvalidRequestException('Invalid format for "'.$key.'"', 400);
        }
    }

    /**
     * Remove items from an array if they are not in the allowed list
     * @param $array
     * @param $validation
     * @throws InvalidRequestException
     */
    public static function validateOptions($array, $validation) : void {
        try {
            foreach($array as $attribute => $value) {
                if(isset($validation[$attribute])) {
                    switch ($validation[$attribute]) {
                        case 'int':
                            self::validateInt($value, $attribute);
                            break;
                        case 'date':
                            self::validateDate($value, "Y-m-d" ,$attribute);
                            break;
                        case 'monthly':
                            self::validateDate($value, "Y-m" ,$attribute);
                            break;
                        case 'accType':
                            self::validateAccType($value, $attribute);
                            break;
                    }
                }
            }
        } catch (InvalidRequestException $e) {
            throw new InvalidRequestException($e->getMessage(), 400);
        }
    }

}
