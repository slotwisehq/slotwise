export interface AdminTenant {
  id: number
  name: string
  logo_path: string | null
}

export interface AdminService {
  id: number
  name: string
  duration_minutes: number
  price: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface AdminStaff {
  id: number
  name: string
  bio: string | null
  avatar_path: string | null
  avatar_url: string | null
}

export interface AdminScheduleDay {
  day_of_week: number
  enabled: boolean
  start_time: string
  end_time: string
}

export interface AdminCustomer {
  id: number
  name: string
  email: string
  phone: string | null
}

export interface AdminAppointment {
  id: number
  starts_at: string
  endss_at: string
  status: 'pending' | 'confirmed' | 'cancelled' | 'no_show'
  notes: string | null
  service: { id: number, name: string}
  staff: { id: number, name: string}
  customer: AdminCustomer
}

export interface PaginatedAppointments {
  data: AdminAppointment[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: { url: string | null, label: string, active: boolean }[]
}

export interface AdminBookingFilters {
  date_from: string | null
  date_to: string | null
  staff_id: number | null
  status: string | null
}

export interface AdminAppointmentGroup {
  date: string
  appointments: AdminAppointment[]
}
