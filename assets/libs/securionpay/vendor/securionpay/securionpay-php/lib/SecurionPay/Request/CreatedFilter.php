<?php
namespace SecurionPay\Request;

class CreatedFilter extends AbstractRequest
{

    public function getGt()
    {
        return $this->get('gt');
    }

    public function gt($gt)
    {
        return $this->set('gt', $gt);
    }

    public function getGte()
    {
        return $this->get('gte');
    }

    public function gte($gte)
    {
        return $this->set('gte', $gte);
    }

    public function getLt()
    {
        return $this->get('lt');
    }

    public function lt($lt)
    {
        return $this->set('lt', $lt);
    }

    public function getLte()
    {
        return $this->get('lte');
    }

    public function lte($lte)
    {
        return $this->set('lte', $lte);
    }
}
