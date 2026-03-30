#ifndef FSMLIST__HPP
#define FSMLIST__HPP

#include "tinyfsm.hpp"

#include "DryerFSM.hpp"

typedef tinyfsm::FsmList<DryerFSM> fsm_list;

/* wrapper to fsm_list::dispatch() */
template<typename E>
void send_event(E const & event)
{
    fsm_list::template dispatch<E>(event);
}

#endif //FSMLIST__HPP