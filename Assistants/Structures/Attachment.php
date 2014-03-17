<?php 


/**
 * @file Attachment.php contains the Attachment class
 */

/**
 * the attachment structure
 *
 * @author Till Uhlig
 */
class Attachment extends Object implements JsonSerializable
{

    /**
     * @var string $id db id of the attachment
     */
    private $id = null;

    /**
     * the $id getter
     *
     * @return the value of $id
     */
    public function getId( )
    {
        return $this->id;
    }

    /**
     * the $id setter
     *
     * @param string $value the new value for $id
     */
    public function setId( $value )
    {
        $this->id = $value;
    }

    /**
     
     * @var string $exerciseId The id of the exercise this attachment belongs to.
     */
    private $exerciseId = null;

    /**
     * the $exerciseId getter
     *
     * @return the value of $exerciseId
     */
    public function getExerciseId( )
    {
        return $this->exerciseId;
    }

    /**
     * the $exerciseId setter
     *
     * @param string $value the new value for $exerciseId
     */
    public function setExerciseId( $value )
    {
        $this->exerciseId = $value;
    }

    /**
     * @var File $file The file of the attachment.
     */
    private $file = null;

    /**
     * the $file getter
     *
     * @return the value of $file
     */
    public function getFile( )
    {
        return $this->file;
    }

    /**
     * the $file setter
     *
     * @param file $value the new value for $file
     */
    public function setFile( $value )
    {
        $this->file = $value;
    }

    /**
     * Creates an Attachment object, for database post(insert) and put(update).
     * Not needed attributes can be set to null.
     *
     * @param string $attachmentId The id of the attachment.
     * @param string $exerciseId The id of the exercise.
     * @param string $fileId The id of the fileId.
     *
     * @return an attachment object.
     */
    public static function createAttachment( 
                                            $attachmentId,
                                            $exerciseId,
                                            $fileId
                                            )
    {
        return new Attachment( array( 
                                     'id' => $attachmentId,
                                     'exerciseId' => $exerciseId,
                                     'file' => File::createFile( 
                                                                $fileId,
                                                                null,
                                                                null,
                                                                null,
                                                                null,
                                                                null
                                                                )
                                     ) );
    }

    /**
     * returns an mapping array to convert between database and structure
     *
     * @return the mapping array
     */
    public static function getDbConvert( )
    {
        return array( 
                     'A_id' => 'id',
                     'E_id' => 'exerciseId',
                     'F_file' => 'file'
                     );
    }

    /**
     * converts an object to insert/update data
     *
     * @return a comma separated string e.g. "a=1,b=2"
     */
    public function getInsertData( )
    {
        $values = '';

        if ( $this->id != null )
            $this->addInsertData( 
                                 $values,
                                 'A_id',
                                 DBJson::mysql_real_escape_string( $this->id )
                                 );
        if ( $this->exerciseId != null )
            $this->addInsertData( 
                                 $values,
                                 'E_id',
                                 DBJson::mysql_real_escape_string( $this->exerciseId )
                                 );
        if ( $this->file != null && 
             $this->file->getFileId( ) != null )
            $this->addInsertData( 
                                 $values,
                                 'F_id',
                                 DBJson::mysql_real_escape_string( $this->file->getFileId( ) )
                                 );

        if ( $values != '' ){
            $values = substr( 
                             $values,
                             1
                             );
        }
        return $values;
    }

    /**
     * returns a sting/string[] of the database primary key/keys
     *
     * @return the primary key/keys
     */
    public static function getDbPrimaryKey( )
    {
        return'A_id';
    }

    /**
     * the constructor
     *
     * @param $data an assoc array with the object informations
     */
    public function __construct( $data = array( ) )
    {
        foreach ( $data AS $key => $value ){
            if ( isset( $key ) ){
                if ( $key == 'file' ){
                    $this->{
                        $key
                        
                    } = File::decodeFile( 
                                         $value,
                                         false
                                         );
                    
                } else 
                    $this->{
                    $key
                    
                } = $value;
            }
        }
    }

    /**
     * encodes an object to json
     *
     * @param $data the object
     *
     * @return the json encoded object
     */
    public static function encodeAttachment( $data )
    {
        return json_encode( $data );
    }

    /**
     * decodes $data to an object
     *
     * @param string $data json encoded data (decode=true)
     * or json decoded data (decode=false)
     * @param bool $decode specifies whether the data must be decoded
     *
     * @return the object
     */
    public static function decodeAttachment( 
                                            $data,
                                            $decode = true
                                            )
    {
        if ( $decode && 
             $data == null )
            $data = '{}';

        if ( $decode )
            $data = json_decode( $data );
        if ( is_array( $data ) ){
            $result = array( );
            foreach ( $data AS $key => $value ){
                $result[] = new Attachment( $value );
            }
            return $result;
            
        } else 
            return new Attachment( $data );
    }

    /**
     * the json serialize function
     *
     * @return an array to serialize the object
     */
    public function jsonSerialize( )
    {
        $list = array( );
        if ( $this->id !== null )
            $list['id'] = $this->id;
        if ( $this->exerciseId !== null )
            $list['exerciseId'] = $this->exerciseId;
        if ( $this->file !== null )
            $list['file'] = $this->file;
        return $list;
    }

    public static function ExtractAttachment( 
                                             $data,
                                             $singleResult = false
                                             )
    {

        // generates an assoc array of files by using a defined list of
        // its attributes
        $files = DBJson::getObjectsByAttributes( 
                                                $data,
                                                File::getDBPrimaryKey( ),
                                                File::getDBConvert( )
                                                );

        // generates an assoc array of attachments by using a defined list of
        // its attributes
        $attachments = DBJson::getObjectsByAttributes( 
                                                      $data,
                                                      Attachment::getDBPrimaryKey( ),
                                                      Attachment::getDBConvert( )
                                                      );

        // concatenates the attachments and the associated files
        $res = DBJson::concatObjectListsSingleResult( 
                                                     $data,
                                                     $attachments,
                                                     Attachment::getDBPrimaryKey( ),
                                                     Attachment::getDBConvert( )['F_file'],
                                                     $files,
                                                     File::getDBPrimaryKey( )
                                                     );

        // to reindex
        $res = array_merge( $res );

        if ( $singleResult == true ){

            // only one object as result
            if ( count( $res ) > 0 )
                $res = $res[0];
        }

        return $res;
    }
}

 
?>
