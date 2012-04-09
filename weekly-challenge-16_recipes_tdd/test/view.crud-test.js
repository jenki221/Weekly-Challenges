buster.spec.expose(); // Make some functions global

describe( "the view", function () {
    it( "exists", function () {
        expect( gotofritz.view.test() ).toBeType( "string" );
    });
});