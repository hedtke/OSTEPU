<?php
// TODO: Passwortverwaltung
// TODO: Verschluesselung

// Fragen

    include_once( 'Structures/ApprovalCondition.php' );  
    include_once( 'Structures/Attachment.php' );  
    include_once( 'Structures/Backup.php' );   
    include_once( 'Structures/Component.php' );   
    include_once( 'Structures/Course.php' );   
    include_once( 'Structures/CourseStatus.php' );   
    include_once( 'Structures/Exercise.php' );   
    include_once( 'Structures/ExerciseSheet.php' );  
    include_once( 'Structures/ExerciseType.php' );   
    include_once( 'Structures/ExternalId.php' );     
    include_once( 'Structures/File.php' );   
    include_once( 'Structures/Group.php' );   
    include_once( 'Structures/Invitation.php' );   
    include_once( 'Structures/Link.php' );   
    include_once( 'Structures/Marking.php' );   
    include_once( 'Structures/Query.php' );   
    include_once( 'Structures/Session.php' ); 
    include_once( 'Structures/Submission.php' );   
    include_once( 'Structures/TutorAssignment.php' );   
    include_once( 'Structures/User.php' );   

/**
* 
*/
abstract class Object
{
    /**
     * Possibly unnecessary
     * a string that identifies who sent the object
     *
     * type: string
     */
   /* private $_sender;
    public function getSender(){
        return $this->_sender;
    }
    public function setSender($_value){
        $this->_sender = $_value;
    }*/
    
        
    protected function addInsertData(&$a, $b, $c){
        $a = $a . ',' . $b . '=\'' . $c . '\'';
    }
}

?>