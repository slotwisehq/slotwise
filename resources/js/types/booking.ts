export interface BookingTenant {
  slug: string
  name: string
  logo_url: string | null
}

export interface BookingService {
  id: number
  name: string
  duration_minutes: number
  price: string
}

export interface BookingStaff {
  id: number
  name: string
  bio: string | null
  avatar_url: string | null
}

export interface BookingSlot {
  time: string // "09:00"
  starts_at: string // "2025-01-06 09:00:00"
}

export interface BookingAppointmentSummary {
  id: number
  service_name: string
  staff_name: string
  customer_name: string
  starts_at: string // ISO8601
  ends_at: string // ISO8601
}
