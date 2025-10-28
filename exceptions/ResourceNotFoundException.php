<?php
/**
 * Resource Not Found Exception
 * Thrown when a resource is not found
 */

class ResourceNotFoundException extends AppException {
    protected $resource_type;
    protected $resource_id;

    public function __construct(
        $resource_type = "Resource",
        $resource_id = null,
        $code = 404,
        $context = []
    ) {
        $this->resource_type = $resource_type;
        $this->resource_id = $resource_id;
        $message = "$resource_type not found";
        if ($resource_id) {
            $message .= " (ID: $resource_id)";
        }
        parent::__construct($message, $code, null, $context);
    }

    public function getResourceType() {
        return $this->resource_type;
    }

    public function getResourceId() {
        return $this->resource_id;
    }

    public function toArray() {
        $data = parent::toArray();
        $data['resource_type'] = $this->resource_type;
        $data['resource_id'] = $this->resource_id;
        return $data;
    }
}

?>
