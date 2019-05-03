angular.module('openITCOCKPIT')
    .controller('LocationsEditController', function($scope, $http, $state, NotyService, $stateParams){

        $scope.id = $stateParams.id;

        $scope.load = function(){
            $http.get("/locations/edit/" + $scope.id + ".json", {
                params: {
                    'angular': true
                }
            }).then(function(result){
                $scope.post = result.data.location;

                $scope.init = false;
            }, function errorCallback(result){
                if(result.status === 403){
                    $state.go('403');
                }

                if(result.status === 404){
                    $state.go('404');
                }
            });
        };

        $scope.submit = function(){
            $http.post("/locations/edit/" + $scope.id + ".json?angular=true",
                $scope.post
            ).then(function(result){

                var url = $state.href('LocationsEdit', {id: result.data.id});
                NotyService.genericSuccess({
                    message: '<u><a href="' + url + '" class="txt-color-white"> '
                        + $scope.successMessage.objectName
                        + '</a></u> ' + $scope.successMessage.message
                });
                $state.go('LocationsIndex').then(function(){
                    NotyService.scrollTop();
                });
            }, function errorCallback(result){
                NotyService.genericError();
                if(result.data.hasOwnProperty('error')){
                    $scope.errors = result.data.error;
                }
            });
        };

        $scope.load();

    });
