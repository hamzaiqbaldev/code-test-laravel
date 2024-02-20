Comments & observations on code

-> Server side validation was missing on data before passing it to repository classes. I have added on a couple of places for reference.

-> Data in repository should be checked with isset or any relavant check before assigning it to model attribute. (added alternative solution in code on few instances) 

-> Failed scenarios should be handled with proper error messages/response to the request. Like if an intance is not found based on passed id, it should handle the response/error message.

-> Some of the functions in repository could be reduced in length and breakup into more than one functions (for better readability and code management).

-> Less use of the if else statements and more use of the conditional assignment statements can improve the overall code.


Apart from this I think could looks good. Use of Laravel events and logs is good to handle backend tasks.



