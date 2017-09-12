<?php

function find_by_email ( $url ) 
{
    $obj = null;

    $response = wp_remote_get( $url );

    $result = json_decode( $response[ 'body' ] );
        
    if( $result->success && count( $result->data ) > 0 )
    {
        $obj = array_shift( $result->data );
    }

    return $obj;
}

function create_person( $url, $data ) 
{
    $person = null;
        
    $response = wp_remote_post( $url, 
                [
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => [
                        "owner_id" => $data->owner_id,
                        "name" => $data->name,
                        "email" => $data->email,
                        "phone" => $data->phone,
                        "visible_to" => 3,
                    ],
                    'cookies' => array()
                ]
            );

    $result = json_decode( $response[ 'body' ] );            

    if( $result && $result->success && count( $result->data ) > 0 ) 
    {
        $person = $result->data;
    }

    return $person;
}

function create_deal( $url, $person, $data )
{
    $deal = null;

    $response = wp_remote_post( $url, 
                [
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' =>  [
                        "user_id" => $data->owner_id,
                        "title" => $person->name,
                        "person_id" => $person->id,
                        "value" => $data->value, 
                        "currency" => $data->currency,
                        "visible_to" => 3 
                    ],
                    'cookies' => array()
                ]
            );

    $result = json_decode( $response[ 'body' ] );

    if( $result && $result->success && count( $result->data ) > 0 )
    {
        $deal = $result->data;
    }

    return $deal;
}

function create_note( $url, $person, $deal, $data )
{
    $note = null;

    $response = wp_remote_post( $url, 
                [
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => array(),
                    'body' => [
                        'content' => "<p>$data->message</p>",
                        'deal_id' => $deal->id,
                        'person_id' => $person->id,
                    ],
                    'cookies' => array()
                ]
            );

    $result = json_decode( $response[ 'body' ] );

    if( $result && $result->success && count( $result->data ) > 0 )
    {
        $note = $result->data;
    }

    return $note;
}

function send_to_pipedrive( $base_uri, $token, $contact ) 
{
    $person = null;
    $deal = null;
    $note = null;

    if( email_is_valid( $contact->email ) )
    {
        $url_find_person_by_email = build_url( $base_uri, "persons/find", $token, [ "term" => $contact->email, "search_by_email" => 1 ] );
    
        $person = find_by_email( $url_find_person_by_email );

        if( $person == null ) 
        {
            $url_person = build_url( $base_uri, "persons", $token );
            $person = create_person( $url_person, $contact );
        }

        $url_deal = build_url( $base_uri, "deals", $token );
        $deal = create_deal( $url_deal, $person, $contact );
        
        $url_note = build_url( $base_uri, "notes", $token );
        $note = create_note( $url_note, $person, $deal, $contact );

    }

    return json_encode ( 
        [ 
            "person" => $person,
            "deal" => $deal,
            "note" => $note, 
        ]
    );
}

function build_url($base_uri, $resource, $token, $filters = array()) 
{
    if( substr( $base_uri, -1) != "/" )
    {
        $base_uri .= "/"; 
    }

    $url = $base_uri . "$resource?api_token=$token";

    if(count( $filters ) > 0)
    {
        $url .= "&" . http_build_query($filters);
    
    }

    return $url;
}

function email_is_valid( $email ) 
{
    $is_valid = false;
    $pattern = "/\A[\w+\-.]+@[a-z\d\-]+(\.[a-z\d\-]+)*\.[a-z]+\z/";
    $is_valid = preg_match( $pattern, $email );

    return $is_valid;
}