<?php

    interface SetInterface
    {
        public function has($item);
    
        public function add($item);
    
        public function delete($item);
    
        public function clear();
        
        public function values();
    }



    class Set implements \ArrayAccess, SetInterface {

        public $length;
        protected $data;
        protected $stub = 1; // this is used as the value in the assoc array, we are disinterested in this
        private $element_count_constraint;
        private $element_count;

        public function __construct() {
            if(func_num_args() > 1){
                $arr = func_get_arg(0);
                $this->element_count_constraint = func_get_arg(1);
                $this->length = count($arr);
                $this->element_count = $this->length;
                foreach ($arr as $item) {
                    // We are using a pointer to save on memory
                    $this->data[$item] = &$this->stub;
            }
            }else{
                $this->element_count_constraint = func_get_arg(0);
                $this->length = 0;
                $this->element_count =  0;
            }
        }

        public function has($item) {
            return isset($this->data[$item]);
        }
    
        public function add($item) {
            if($this->elementCountConstraintOK()){
                $this->data[$item] = &$this->stub;
                $this->length = count($this->data);
                $this->element_count = count($this->data);
            }
        }
    
        public function delete($item) {
            unset($this->data[$item]);
            $this->length = count($this->data);
        }
    
        public function clear() {
            $this->data = [];
            $this->length = 0;
        }
    
        public function values() {
            $arr_keys = array_keys($this->data);
            sort($arr_keys);
            return $arr_keys;
        }
    
        //Custom Serializer method
        public function serializeToString(){
            $arr_keys = array_keys($this->data);
            sort($arr_keys);
            return join("+", $arr_keys);
        }

        public function elementCountConstraintOK(){
            return $this->element_count < $this->element_count_constraint;
        }

        public function offsetSet($offset, $value) {
            if (is_null($offset)) {
                $this->data[] = $value;
            } else {
                $this->data[$offset] = $value;
            }
        }
    
        public function offsetExists($offset) {
            return isset($this->data[$offset]);
        }
    
        public function offsetUnset($offset) {
            unset($this->data[$offset]);
        }
    
        public function offsetGet($offset) {
            return isset($this->data[$offset]) ? $this->data[$offset] : null;
            
        }
    }
?>