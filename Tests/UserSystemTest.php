class UserSystemTest extends PHPUnit_Framework_TestCase {
    public function testConstructFailOnUnsetOptions() {
        $a = new UserSystem([],false);
        $this->assertEquals(true, $a);
    }
}
